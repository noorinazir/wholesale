<?php

namespace Tests\Feature;

use App\Models\EmailQueue;
use App\Models\Vendor;
use App\Services\EmailSendingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EmailQueueAtomicClaimTest extends TestCase
{
    use RefreshDatabase;

    public function test_queue_item_is_claimed_only_once(): void
    {
        $vendor = Vendor::factory()->create();

        $queueItem = EmailQueue::create([
            'vendor_id' => $vendor->id,
            'recipient_email' => 'vendor@example.com',
            'subject' => 'Test Subject',
            'body' => 'Test Body',
            'status' => 'pending',
            'attempts' => 0,
            'max_attempts' => 3,
        ]);

        /** @var EmailSendingService $service */
        $service = app(EmailSendingService::class);

        $firstClaim = $service->claimNextQueueItem();
        $secondClaim = $service->claimNextQueueItem();

        $this->assertNotNull($firstClaim);
        $this->assertSame($queueItem->id, $firstClaim->id);
        $this->assertSame('sending', $firstClaim->status);
        $this->assertSame(1, $firstClaim->attempts);
        $this->assertNull($secondClaim);

        $fresh = EmailQueue::findOrFail($queueItem->id);
        $this->assertSame('sending', $fresh->status);
        $this->assertSame(1, $fresh->attempts);
    }
}
