<?php

namespace Tests\Feature;

use App\Models\Campaign;
use App\Models\GeneratedEmail;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class GlobalSearchPermissionTest extends TestCase
{
    use RefreshDatabase;

    public function test_global_search_returns_no_results_without_view_permissions(): void
    {
        $restrictedRole = Role::firstOrCreate(['name' => 'restricted', 'guard_name' => 'web']);

        $user = User::factory()->create(['role' => 'restricted']);
        $user->assignRole($restrictedRole);

        $vendor = Vendor::factory()->create(['brand_name' => 'Alpha Supplies']);

        $campaign = Campaign::create([
            'user_id' => $user->id,
            'name' => 'Alpha Campaign',
            'objective' => 'Outreach',
            'status' => 'draft',
        ]);

        GeneratedEmail::create([
            'vendor_id' => $vendor->id,
            'campaign_id' => $campaign->id,
            'user_id' => $user->id,
            'subject' => 'Alpha Subject',
            'body' => 'Body',
            'tone' => 'professional',
            'status' => 'draft',
        ]);

        $this->actingAs($user);

        $response = $this->getJson(route('search', ['q' => 'Alpha']));

        $response->assertOk();
        $response->assertExactJson([]);
    }
}
