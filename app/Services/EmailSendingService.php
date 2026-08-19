<?php

namespace App\Services;

use App\Models\EmailQueue;
use App\Models\EmailLog;
use App\Models\SmtpSetting;
use App\Models\SuppressionList;
use App\Models\SystemSetting;
use App\Models\Vendor;
use App\Models\Notification;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Mail\Mailables\Address;
use App\Mail\VendorOutreachMail;
use App\Services\FollowUpService;

class EmailSendingService
{
    public function isSendingPaused(): bool
    {
        return SystemSetting::get('sending_paused', false);
    }

    public function pauseSending(): void
    {
        SystemSetting::set('sending_paused', true, 'sending');
    }

    public function resumeSending(): void
    {
        SystemSetting::set('sending_paused', false, 'sending');
    }

    public function isTestMode(): bool
    {
        $smtp = SmtpSetting::where('is_active', true)->first();
        return $smtp?->test_mode ?? false;
    }

    public function getTestModeRecipient(): ?string
    {
        $smtp = SmtpSetting::where('is_active', true)->first();
        return $smtp?->test_mode_recipient;
    }

    public function getDailyLimit(): int
    {
        return (int) SystemSetting::get('daily_email_limit', 50);
    }

    public function getHourlyLimit(): int
    {
        return (int) SystemSetting::get('hourly_email_limit', 10);
    }

    public function getDailySentCount(): int
    {
        return EmailQueue::where('status', 'sent')
            ->whereDate('sent_at', today())
            ->count();
    }

    public function getHourlySentCount(): int
    {
        return EmailQueue::where('status', 'sent')
            ->where('sent_at', '>=', now()->subHour())
            ->count();
    }

    public function isDailyLimitReached(): bool
    {
        return $this->getDailySentCount() >= $this->getDailyLimit();
    }

    public function isHourlyLimitReached(): bool
    {
        return $this->getHourlySentCount() >= $this->getHourlyLimit();
    }

    public function isWithinSendingSchedule(): bool
    {
        $startTime = SystemSetting::get('sending_start_time', '09:00');
        $endTime = SystemSetting::get('sending_end_time', '17:00');

        if (empty($startTime) || empty($endTime)) {
            return true;
        }

        $timezone = SystemSetting::get('sending_timezone', config('app.timezone'));
        $allowedDays = json_decode(SystemSetting::get('allowed_weekdays', '["1","2","3","4","5"]'), true);

        try {
            $now = now($timezone);
        } catch (\Exception $e) {
            $now = now();
        }

        $dayOfWeek = (string) $now->dayOfWeekIso;

        if (!in_array($dayOfWeek, $allowedDays)) {
            return false;
        }

        $currentTime = $now->format('H:i');
        return $currentTime >= $startTime && $currentTime <= $endTime;
    }

    public function canSend(): bool
    {
        if ($this->isSendingPaused()) {
            return false;
        }

        if (!$this->isWithinSendingSchedule()) {
            return false;
        }

        if ($this->isDailyLimitReached()) {
            return false;
        }

        if ($this->isHourlyLimitReached()) {
            return false;
        }

        return true;
    }

    public function getDelaySeconds(): int
    {
        $delayType = SystemSetting::get('delay_type', 'random');
        $minDelay = (int) SystemSetting::get('min_delay_seconds', 60);
        $maxDelay = (int) SystemSetting::get('max_delay_seconds', 180);

        if ($delayType === 'fixed') {
            return $minDelay;
        }

        return rand($minDelay, $maxDelay);
    }

    public function isVendorSuppressed(Vendor $vendor): bool
    {
        if ($vendor->isOptedOut()) {
            return true;
        }

        if ($vendor->contact_email && SuppressionList::isSuppressed($vendor->contact_email)) {
            return true;
        }

        return false;
    }

    public function hasDuplicateSent(int $vendorId, ?int $campaignId): bool
    {
        if (!$campaignId) {
            return false;
        }

        return EmailQueue::where('vendor_id', $vendorId)
            ->where('campaign_id', $campaignId)
            ->where('status', 'sent')
            ->exists();
    }

    public function hasPendingQueueItem(int $vendorId, ?int $campaignId): bool
    {
        $query = EmailQueue::where('vendor_id', $vendorId)
            ->whereIn('status', ['pending', 'scheduled', 'sending']);

        if ($campaignId) {
            $query->where('campaign_id', $campaignId);
        }

        return $query->exists();
    }

    public function configureSmtp(): ?SmtpSetting
    {
        $smtp = SmtpSetting::where('is_active', true)->first();

        if (!$smtp) {
            return null;
        }

        $password = $smtp->getDecryptedPassword();

        Config::set('mail.default', 'smtp');
        Config::set('mail.mailers.smtp.host', $smtp->host);
        Config::set('mail.mailers.smtp.port', $smtp->port);
        Config::set('mail.mailers.smtp.encryption', $smtp->encryption === 'none' ? null : $smtp->encryption);
        Config::set('mail.mailers.smtp.username', $smtp->username);
        Config::set('mail.mailers.smtp.password', $password);
        Config::set('mail.from.address', $smtp->from_email);
        Config::set('mail.from.name', $smtp->from_name);

        if ($smtp->reply_to) {
            Config::set('mail.reply_to.address', $smtp->reply_to);
        }

        return $smtp;
    }

    public function sendQueueItem(EmailQueue $item): array
    {
        $smtp = $this->configureSmtp();

        if (!$smtp) {
            $item->update([
                'status' => 'failed',
                'last_error' => 'No active SMTP configuration found',
                'attempts' => $item->attempts + 1,
            ]);

            $this->createLog($item, 'failed', null, 'No active SMTP configuration found');
            return ['success' => false, 'error' => 'No SMTP configuration'];
        }

        $recipient = $item->recipient_email;

        if ($this->isTestMode()) {
            $recipient = $this->getTestModeRecipient() ?? 'admin@example.com';
            Log::info("Test mode: redirecting email from {$item->recipient_email} to {$recipient}");
        }

        $item->update(['status' => 'sending', 'attempts' => $item->attempts + 1]);

        try {
            $mailable = new VendorOutreachMail($item->subject, $item->body, $smtp);
            $mailable->to($recipient);

            if ($smtp->reply_to) {
                $mailable->replyTo($smtp->reply_to);
            }

            Mail::send($mailable);

            $sentAt = now();
            $item->update([
                'status' => 'sent',
                'sent_at' => $sentAt,
                'smtp_response' => 'Accepted',
            ]);

            $this->createLog($item, 'sent', 'Accepted', null);

            $item->vendor->update([
                'email_status' => 'sent',
                'last_contacted_at' => $sentAt,
                'status' => 'contacted',
            ]);

            if ($item->campaign_id) {
                $item->campaign->vendors()->updateExistingPivot($item->vendor_id, [
                    'status' => 'sent',
                    'sent_at' => $sentAt,
                ]);
            }

            try {
                $followUpService = app(FollowUpService::class);
                $followUpService->createFollowUpSequence($item);
            } catch (\Exception $e) {
                Log::warning('Failed to create follow-up sequence', ['error' => $e->getMessage()]);
            }

            $this->clearDashboardCache();

            return ['success' => true];

        } catch (\Exception $e) {
            Log::error('Email sending failed', [
                'queue_id' => $item->id,
                'recipient' => $recipient,
                'error' => $e->getMessage(),
            ]);

            $item->update([
                'status' => 'failed',
                'last_error' => $e->getMessage(),
            ]);

            if ($item->campaign_id) {
                $item->campaign->vendors()->updateExistingPivot($item->vendor_id, [
                    'status' => 'failed',
                ]);
            }

            $this->createLog($item, 'failed', null, $e->getMessage());

            Notification::createForAll('error', 'Email Send Failed', "Failed to send to {$item->recipient_email}: {$e->getMessage()}", ['queue_id' => $item->id]);

            $this->clearDashboardCache();

            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public function testSmtpConnection(SmtpSetting $smtp, string $testEmail): array
    {
        $password = $smtp->getDecryptedPassword();
        $encryption = $smtp->encryption === 'none' ? null : $smtp->encryption;

        Config::set('mail.default', 'smtp');
        Config::set('mail.mailers.smtp.host', $smtp->host);
        Config::set('mail.mailers.smtp.port', $smtp->port);
        Config::set('mail.mailers.smtp.encryption', $encryption);
        Config::set('mail.mailers.smtp.username', $smtp->username);
        Config::set('mail.mailers.smtp.password', $password);
        Config::set('mail.from.address', $smtp->from_email);
        Config::set('mail.from.name', $smtp->from_name);

        Log::info('SMTP test attempt', [
            'host' => $smtp->host,
            'port' => $smtp->port,
            'encryption' => $encryption,
            'username' => $smtp->username,
            'from' => $smtp->from_email,
            'to' => $testEmail,
        ]);

        try {
            Mail::raw('This is a test email from your Wholesale Outreach Platform. If you received this, your SMTP configuration is working correctly.', function ($message) use ($testEmail, $smtp) {
                $message->to($testEmail)
                    ->subject('SMTP Test - Wholesale Outreach Platform');
                if ($smtp->reply_to) {
                    $message->replyTo($smtp->reply_to);
                }
            });

            $smtp->update([
                'last_tested_at' => now(),
                'last_test_success' => true,
            ]);

            return ['success' => true, 'message' => 'Test email sent successfully to ' . $testEmail];
        } catch (\Exception $e) {
            Log::error('SMTP test failed', ['error' => $e->getMessage()]);
            $smtp->update([
                'last_tested_at' => now(),
                'last_test_success' => false,
            ]);

            return ['success' => false, 'message' => 'SMTP test failed: ' . $e->getMessage()];
        }
    }

    public function cancelAllPending(): int
    {
        return EmailQueue::whereIn('status', ['pending', 'scheduled'])
            ->update(['status' => 'cancelled']);
    }

    private function createLog(EmailQueue $item, string $status, ?string $smtpResponse, ?string $error): void
    {
        EmailLog::create([
            'vendor_id' => $item->vendor_id,
            'campaign_id' => $item->campaign_id,
            'generated_email_id' => $item->generated_email_id,
            'email_queue_id' => $item->id,
            'recipient' => $item->recipient_email,
            'subject' => $item->subject,
            'body' => $item->body,
            'created_at' => $item->created_at,
            'scheduled_at' => $item->scheduled_at,
            'sent_at' => $status === 'sent' ? now() : null,
            'status' => $status,
            'smtp_response' => $smtpResponse,
            'error' => $error,
            'message_id' => $item->message_id,
        ]);
    }

    private function clearDashboardCache(): void
    {
        Cache::forget('dashboard.stats');
        Cache::forget('dashboard.funnel');
        Cache::forget('dashboard.products');
        Cache::forget('dashboard.ai');
        Cache::forget('dashboard.breakdown');
        Cache::forget('dashboard.email_chart');
        Cache::forget('dashboard.status_chart');
        Cache::forget('analytics.overview');
        Cache::forget('analytics.daily');
        Cache::forget('analytics.success_fail');
        Cache::forget('analytics.category');
        Cache::forget('analytics.ai_usage');
    }
}
