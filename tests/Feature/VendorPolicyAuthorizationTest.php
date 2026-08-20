<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Vendor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VendorPolicyAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_viewer_can_access_vendor_index_but_cannot_create_vendor(): void
    {
        $viewer = User::factory()->create(['role' => 'viewer']);
        $viewer->assignRole('viewer');

        $this->actingAs($viewer)
            ->get(route('vendors.index'))
            ->assertOk();

        $this->actingAs($viewer)
            ->post(route('vendors.create'), [
                'brand_name' => 'Unauthorized Vendor',
                'priority' => 'medium',
            ])
            ->assertForbidden();
    }

    public function test_staff_can_create_vendor(): void
    {
        $staff = User::factory()->create(['role' => 'staff']);
        $staff->assignRole('staff');

        $this->actingAs($staff)
            ->post(route('vendors.create'), [
                'brand_name' => 'Created Vendor',
                'company_name' => 'Created Co',
                'contact_email' => 'created@example.com',
                'priority' => 'medium',
                'status' => 'new',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('vendors', [
            'brand_name' => 'Created Vendor',
            'contact_email' => 'created@example.com',
        ]);
    }

    public function test_viewer_cannot_archive_vendor(): void
    {
        $viewer = User::factory()->create(['role' => 'viewer']);
        $viewer->assignRole('viewer');

        $vendor = Vendor::factory()->create();

        $this->actingAs($viewer)
            ->delete(route('vendors.destroy', $vendor->id))
            ->assertForbidden();

        $this->assertNotSame('archived', $vendor->fresh()->status);
    }
}
