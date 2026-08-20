<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductPolicyAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_viewer_can_access_product_index_but_cannot_create_product(): void
    {
        $viewer = User::factory()->create(['role' => 'viewer']);
        $viewer->assignRole('viewer');

        $vendor = Vendor::factory()->create();

        $this->actingAs($viewer)
            ->get(route('products.index'))
            ->assertOk();

        $this->actingAs($viewer)
            ->post(route('products.store'), [
                'vendor_id' => $vendor->id,
                'product_name' => 'Unauthorized Product',
            ])
            ->assertForbidden();
    }

    public function test_staff_with_manage_products_can_create_product(): void
    {
        $staff = User::factory()->create(['role' => 'staff']);
        $staff->assignRole('staff');

        $vendor = Vendor::factory()->create();

        $this->actingAs($staff)
            ->post(route('products.store'), [
                'vendor_id' => $vendor->id,
                'product_name' => 'Created Product',
                'status' => 'active',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('products', [
            'vendor_id' => $vendor->id,
            'product_name' => 'Created Product',
        ]);
    }
}
