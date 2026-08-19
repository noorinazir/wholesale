<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Vendor;
use App\Models\Campaign;
use App\Models\EmailQueue;
use App\Models\SuppressionList;
use App\Services\EmailSendingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EmailSendingTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private EmailSendingService $sendingService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create([
            'role' => 'administrator',
            'is_active' => true,
        ]);
        $this->user->assignRole('administrator');
        $this->actingAs($this->user);
        $this->sendingService = app(EmailSendingService::class);
    }

    public function test_pause_sending(): void
    {
        $this->sendingService->pauseSending();
        $this->assertTrue($this->sendingService->isSendingPaused());
    }

    public function test_resume_sending(): void
    {
        $this->sendingService->pauseSending();
        $this->sendingService->resumeSending();
        $this->assertFalse($this->sendingService->isSendingPaused());
    }

    public function test_can_send_returns_false_when_paused(): void
    {
        $this->sendingService->pauseSending();
        $this->assertFalse($this->sendingService->canSend());
    }

    public function test_suppressed_vendor_detected(): void
    {
        $vendor = Vendor::factory()->create([
            'contact_email' => 'suppressed@test.com',
            'status' => 'opted_out',
        ]);

        $this->assertTrue($this->sendingService->isVendorSuppressed($vendor));
    }

    public function test_suppression_list_checked(): void
    {
        SuppressionList::create([
            'email' => 'blocked@test.com',
            'reason' => 'opt_out',
            'suppressed_at' => now(),
        ]);

        $vendor = Vendor::factory()->create([
            'contact_email' => 'blocked@test.com',
        ]);

        $this->assertTrue($this->sendingService->isVendorSuppressed($vendor));
    }

    public function test_duplicate_sent_detection(): void
    {
        $vendor = Vendor::factory()->create();
        $campaign = Campaign::factory()->create(['user_id' => $this->user->id]);

        EmailQueue::create([
            'vendor_id' => $vendor->id,
            'campaign_id' => $campaign->id,
            'recipient_email' => $vendor->contact_email ?? 'test@test.com',
            'subject' => 'Test Subject',
            'body' => 'Test Body',
            'status' => 'sent',
            'sent_at' => now(),
        ]);

        $this->assertTrue($this->sendingService->hasDuplicateSent($vendor->id, $campaign->id));
    }

    public function test_duplicate_sent_returns_false_without_campaign(): void
    {
        $vendor = Vendor::factory()->create();
        $this->assertFalse($this->sendingService->hasDuplicateSent($vendor->id, null));
    }

    public function test_cancel_all_pending(): void
    {
        $vendor1 = Vendor::factory()->create();
        $vendor2 = Vendor::factory()->create();

        EmailQueue::create([
            'vendor_id' => $vendor1->id,
            'recipient_email' => 'test@test.com',
            'subject' => 'Test',
            'body' => 'Body',
            'status' => 'pending',
        ]);

        EmailQueue::create([
            'vendor_id' => $vendor2->id,
            'recipient_email' => 'test2@test.com',
            'subject' => 'Test 2',
            'body' => 'Body 2',
            'status' => 'scheduled',
        ]);

        $count = $this->sendingService->cancelAllPending();
        $this->assertEquals(2, $count);
    }

    public function test_daily_limit_check(): void
    {
        $vendor = Vendor::factory()->create();

        \App\Models\SystemSetting::set('daily_email_limit', '1', 'sending');

        EmailQueue::create([
            'vendor_id' => $vendor->id,
            'recipient_email' => 'test@test.com',
            'subject' => 'Test',
            'body' => 'Body',
            'status' => 'sent',
            'sent_at' => now(),
        ]);

        $this->assertTrue($this->sendingService->isDailyLimitReached());
    }
}
