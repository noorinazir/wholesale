<?php

namespace App\Services;

use App\Models\SmtpSetting;
use App\Models\Vendor;
use App\Models\EmailReply;
use App\Models\EmailLog;
use App\Models\EmailEvent;
use App\Models\Notification;
use App\Services\FollowUpService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Webklex\IMAP\Facades\Client as ImapClientFacade;

class InboxService
{
    public function checkInbox(): array
    {
        $smtp = SmtpSetting::where('is_active', true)->first();

        if (!$smtp || !$smtp->inbox_checking_enabled) {
            return ['success' => false, 'message' => 'Inbox checking is not enabled.'];
        }

        if (!$smtp->imap_host || !$smtp->imap_username) {
            return ['success' => false, 'message' => 'IMAP settings not configured.'];
        }

        $password = $smtp->getDecryptedImapPassword();
        if (!$password) {
            $password = $smtp->getDecryptedPassword();
        }

        if (!$password) {
            return ['success' => false, 'message' => 'No IMAP password configured.'];
        }

        $encryption = $smtp->imap_encryption ?: 'ssl';
        $port = $smtp->imap_port ?: ($encryption === 'ssl' ? 993 : 143);
        $validateCert = !app()->environment(['local', 'testing']);
        $lastUid = (int) ($smtp->last_imap_uid ?? 0);
        $maxUid = $lastUid;

        try {
            $client = ImapClientFacade::make([
                'host' => $smtp->imap_host,
                'port' => $port,
                'protocol' => 'imap',
                'encryption' => $encryption === 'notls' ? 'none' : $encryption,
                'validate_cert' => $validateCert,
                'username' => $smtp->imap_username,
                'password' => $password,
                'timeout' => 30,
            ]);

            $client->connect();

            $folder = $client->getFolder('INBOX');
            if (!$folder) {
                $client->disconnect();
                return ['success' => false, 'message' => 'INBOX folder not found.'];
            }

            $messages = $folder->query()->all()->get();
        } catch (\Exception $e) {
            Log::error('IMAP connection failed: ' . $e->getMessage());
            return ['success' => false, 'message' => 'IMAP connection failed: ' . $e->getMessage()];
        }

        $repliesFound = 0;
        $vendorsUpdated = 0;
        $skipped = 0;

        Log::info('IMAP check: found ' . count($messages) . ' messages');

        foreach ($messages as $message) {
            try {
                $uid = null;
                try {
                    $uid = (int) ($message->getUid() ?? 0);
                } catch (\Exception $e) {
                    $uid = null;
                }

                if ($uid && $uid <= $lastUid) {
                    $skipped++;
                    continue;
                }

                if ($uid && $uid > $maxUid) {
                    $maxUid = $uid;
                }

                $header = $message->getHeader();
                $fromAttr = $header->get('from');
                $fromAddresses = $fromAttr->get();
                $firstFrom = is_array($fromAddresses) ? ($fromAddresses[0] ?? null) : $fromAddresses;
                $fromEmail = strtolower(trim($firstFrom->mail ?? $firstFrom->full ?? 'unknown@unknown'));
                $fromName = $firstFrom->personal ?? null;

                Log::info('IMAP message from: ' . $fromEmail . ' subject: ' . ($message->getSubject() ?? '(none)'));

                $subjectAttr = $header->get('subject');
                $subject = $subjectAttr ? ($subjectAttr->get()[0] ?? '(No Subject)') : '(No Subject)';

                $messageIdAttr = $header->get('message_id');
                $messageId = $messageIdAttr ? ($messageIdAttr->get()[0] ?? null) : null;

                $inReplyToAttr = $header->get('in_reply_to');
                $inReplyTo = $inReplyToAttr ? ($inReplyToAttr->get()[0] ?? null) : null;

                $dateAttr = $header->get('date');
                $date = time();
                if ($dateAttr) {
                    $dateValues = $dateAttr->get();
                    $dateValue = is_array($dateValues) ? ($dateValues[0] ?? null) : $dateValues;
                    if ($dateValue instanceof \Carbon\Carbon) {
                        $date = $dateValue->timestamp;
                    } elseif ($dateValue) {
                        $date = strtotime((string)$dateValue);
                    }
                }

                $existing = EmailReply::where('message_id', $messageId)->whereNotNull('message_id')->first();
                if ($existing) {
                    $skipped++;
                    continue;
                }

                $vendor = Vendor::whereRaw('LOWER(contact_email) = ?', [$fromEmail])->first();
                if (!$vendor) {
                    $vendor = Vendor::whereRaw('LOWER(secondary_email) = ?', [$fromEmail])->first();
                }

                if (!$vendor) {
                    $skipped++;
                    continue;
                }

                $bodyText = '';
                $bodyHtml = null;

                try {
                    $bodyText = $message->getTextBody();
                } catch (\Exception $e) {
                    $bodyText = '';
                }

                if (empty($bodyText)) {
                    try {
                        $bodyHtml = $message->getHTMLBody();
                        $bodyText = strip_tags($bodyHtml);
                    } catch (\Exception $e) {
                        $bodyText = '';
                    }
                }

                $bodyText = trim($bodyText);
                if (strlen($bodyText) > 10000) {
                    $bodyText = substr($bodyText, 0, 10000) . '...';
                }

                $emailLog = null;
                if ($inReplyTo) {
                    $emailLog = EmailLog::where('message_id', $inReplyTo)->first();
                }

                $reply = EmailReply::create([
                    'vendor_id' => $vendor->id,
                    'email_log_id' => $emailLog?->id,
                    'from_email' => $fromEmail,
                    'from_name' => $fromName,
                    'subject' => $subject,
                    'body_text' => $bodyText,
                    'body_html' => $bodyHtml,
                    'message_id' => $messageId,
                    'in_reply_to' => $inReplyTo,
                    'received_at' => date('Y-m-d H:i:s', $date),
                    'is_read' => false,
                ]);

                EmailEvent::create([
                    'email_log_id' => $emailLog?->id,
                    'vendor_id' => $vendor->id,
                    'event_type' => 'reply',
                    'recipient' => $fromEmail,
                    'message_id' => $messageId,
                    'payload' => ['subject' => $subject, 'reply_id' => $reply->id],
                    'occurred_at' => now(),
                ]);

                if (in_array($vendor->status, ['contacted', 'ready_to_contact', 'new', 'researching'])) {
                    $vendor->update([
                        'status' => 'replied',
                        'email_status' => 'replied',
                    ]);
                    $vendorsUpdated++;
                }

                try {
                    app(FollowUpService::class)->cancelFollowUpsForVendor($vendor->id);
                } catch (\Exception $e) {
                    Log::warning('Failed to cancel follow-ups', ['error' => $e->getMessage()]);
                }

                Notification::createForAll('reply', 'New Reply Received', "{$vendor->brand_name} replied to your email: {$subject}", ['vendor_id' => $vendor->id, 'reply_id' => $reply->id]);

                $repliesFound++;
            } catch (\Exception $e) {
                Log::warning('Failed to process message', ['error' => $e->getMessage()]);
                $skipped++;
            }
        }

        $client->disconnect();

        $smtp->update([
            'last_inbox_check_at' => now(),
            'last_imap_uid' => $maxUid > 0 ? $maxUid : null,
        ]);

        if ($repliesFound > 0) {
            Cache::forget('dashboard.stats');
            Cache::forget('dashboard.funnel');
            Cache::forget('dashboard.breakdown');
            Cache::forget('dashboard.status_chart');
            Cache::forget('analytics.overview');
        }

        return [
            'success' => true,
            'replies_found' => $repliesFound,
            'vendors_updated' => $vendorsUpdated,
            'skipped' => $skipped,
            'message' => "Checked inbox: {$repliesFound} replies found, {$vendorsUpdated} vendors updated, {$skipped} skipped.",
        ];
    }
}
