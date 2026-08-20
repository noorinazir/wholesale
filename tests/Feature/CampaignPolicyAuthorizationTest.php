<?php

namespace Tests\Feature;

use App\Models\Campaign;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CampaignPolicyAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_viewer_can_view_campaign_but_cannot_start_it(): void
    {
        $viewer = User::factory()->create(['role' => 'viewer']);
        $viewer->assignRole('viewer');

        $campaign = Campaign::factory()->create(['status' => 'draft']);

        $this->actingAs($viewer)
            ->get(route('campaigns.show', $campaign->id))
            ->assertOk();

        $this->actingAs($viewer)
            ->post(route('campaigns.show', $campaign->id) . '/start')
            ->assertForbidden();

        $this->assertSame('draft', $campaign->fresh()->status);
    }

    public function test_manager_can_add_vendors_and_start_campaign(): void
    {
        $manager = User::factory()->create(['role' => 'manager']);
        $manager->assignRole('manager');

        $campaign = Campaign::factory()->create(['status' => 'draft']);
        $vendor = Vendor::factory()->create();

        $this->actingAs($manager)
            ->post(route('campaigns.show', $campaign->id) . '/add-vendors', [
                'vendor_ids' => [$vendor->id],
            ])
            ->assertSessionHas('status');

        $this->assertSame(1, $campaign->vendors()->count());

        $this->actingAs($manager)
            ->post(route('campaigns.show', $campaign->id) . '/start')
            ->assertSessionHas('status');

        $this->assertSame('active', $campaign->fresh()->status);
    }
}
