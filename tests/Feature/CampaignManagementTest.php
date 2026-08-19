<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Vendor;
use App\Models\Campaign;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CampaignManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create([
            'role' => 'administrator',
            'is_active' => true,
        ]);
        $this->user->assignRole('administrator');
        $this->actingAs($this->user);
    }

    public function test_campaign_index_loads(): void
    {
        $response = $this->get(route('campaigns.index'));
        $response->assertOk();
    }

    public function test_campaign_create_form_loads(): void
    {
        $response = $this->get(route('campaigns.create'));
        $response->assertOk();
    }

    public function test_campaign_can_be_created(): void
    {
        $response = $this->post(route('campaigns.create'), [
            'name' => 'Test Campaign',
            'objective' => 'Wholesale Authorization',
            'description' => 'Test description',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('campaigns', [
            'name' => 'Test Campaign',
            'objective' => 'Wholesale Authorization',
        ]);
    }

    public function test_campaign_show_loads(): void
    {
        $campaign = Campaign::factory()->create(['user_id' => $this->user->id]);
        $response = $this->get(route('campaigns.show', $campaign->id));
        $response->assertOk();
    }

    public function test_vendors_can_be_added_to_campaign(): void
    {
        $campaign = Campaign::factory()->create(['user_id' => $this->user->id]);
        $vendor1 = Vendor::factory()->create(['user_id' => $this->user->id]);
        $vendor2 = Vendor::factory()->create(['user_id' => $this->user->id]);

        $response = $this->post(route('campaigns.show', $campaign->id) . '/add-vendors', [
            'vendor_ids' => [$vendor1->id, $vendor2->id],
        ]);

        $response->assertSessionHas('status');
        $this->assertEquals(2, $campaign->vendors()->count());
    }

    public function test_vendor_can_be_removed_from_campaign(): void
    {
        $campaign = Campaign::factory()->create(['user_id' => $this->user->id]);
        $vendor = Vendor::factory()->create(['user_id' => $this->user->id]);
        $campaign->vendors()->attach($vendor->id, ['status' => 'selected']);

        $response = $this->post(route('campaigns.show', $campaign->id) . '/remove-vendor', [
            'vendor_id' => $vendor->id,
        ]);

        $response->assertSessionHas('status');
        $this->assertEquals(0, $campaign->vendors()->count());
    }

    public function test_campaign_can_be_started(): void
    {
        $campaign = Campaign::factory()->create([
            'user_id' => $this->user->id,
            'status' => 'draft',
        ]);

        $response = $this->post(route('campaigns.show', $campaign->id) . '/start');
        $response->assertSessionHas('status');
        $campaign->refresh();
        $this->assertEquals('active', $campaign->status);
    }

    public function test_campaign_can_be_paused(): void
    {
        $campaign = Campaign::factory()->create([
            'user_id' => $this->user->id,
            'status' => 'active',
        ]);

        $response = $this->post(route('campaigns.show', $campaign->id) . '/pause');
        $response->assertSessionHas('status');
        $campaign->refresh();
        $this->assertEquals('paused', $campaign->status);
    }

    public function test_duplicate_vendor_add_prevented(): void
    {
        $campaign = Campaign::factory()->create(['user_id' => $this->user->id]);
        $vendor = Vendor::factory()->create(['user_id' => $this->user->id]);
        $campaign->vendors()->attach($vendor->id, ['status' => 'selected']);

        $this->post(route('campaigns.show', $campaign->id) . '/add-vendors', [
            'vendor_ids' => [$vendor->id],
        ]);

        $this->assertEquals(1, $campaign->vendors()->count());
    }
}
