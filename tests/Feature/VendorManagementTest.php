<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Vendor;
use App\Models\Company;
use App\Models\Campaign;
use App\Models\GeneratedEmail;
use App\Models\EmailQueue;
use App\Models\SuppressionList;
use App\Services\EmailSendingService;
use App\Services\AuditLogService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VendorManagementTest extends TestCase
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

    public function test_vendor_index_loads(): void
    {
        $response = $this->get(route('vendors.index'));
        $response->assertOk();
        $response->assertViewIs('vendors.index');
    }

    public function test_vendor_create_form_loads(): void
    {
        $response = $this->get(route('vendors.create'));
        $response->assertOk();
    }

    public function test_vendor_can_be_created(): void
    {
        $response = $this->post(route('vendors.create'), [
            'brand_name' => 'Test Brand',
            'company_name' => 'Test Company',
            'contact_name' => 'John Doe',
            'contact_email' => 'john@testbrand.com',
            'product_category' => 'Pet Supplies',
            'priority' => 'medium',
            'status' => 'new',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('vendors', [
            'brand_name' => 'Test Brand',
            'contact_email' => 'john@testbrand.com',
        ]);
    }

    public function test_vendor_duplicate_prevention(): void
    {
        Vendor::factory()->create([
            'brand_name' => 'Duplicate Brand',
            'contact_email' => 'dup@test.com',
        ]);

        $response = $this->post(route('vendors.create'), [
            'brand_name' => 'Duplicate Brand',
            'contact_email' => 'dup@test.com',
            'priority' => 'medium',
        ]);

        $response->assertSessionHas('error');
    }

    public function test_vendor_can_be_updated(): void
    {
        $vendor = Vendor::factory()->create([
            'user_id' => $this->user->id,
            'brand_name' => 'Original Brand',
        ]);

        $response = $this->put(route('vendors.edit', $vendor->id), [
            'brand_name' => 'Updated Brand',
            'priority' => 'high',
            'status' => 'new',
        ]);

        $response->assertSessionHas('status');
        $vendor->refresh();
        $this->assertEquals('Updated Brand', $vendor->brand_name);
        $this->assertEquals('high', $vendor->priority);
    }

    public function test_vendor_can_be_archived(): void
    {
        $vendor = Vendor::factory()->create([
            'user_id' => $this->user->id,
        ]);

        $response = $this->delete(route('vendors.destroy', $vendor->id));

        $response->assertRedirect(route('vendors.index'));
        $vendor->refresh();
        $this->assertEquals('archived', $vendor->status);
    }

    public function test_vendor_show_loads(): void
    {
        $vendor = Vendor::factory()->create([
            'user_id' => $this->user->id,
        ]);

        $response = $this->get(route('vendors.show', $vendor->id));
        $response->assertOk();
    }

    public function test_vendor_search_filters(): void
    {
        Vendor::factory()->create(['brand_name' => 'Alpha Brand', 'user_id' => $this->user->id]);
        Vendor::factory()->create(['brand_name' => 'Beta Brand', 'user_id' => $this->user->id]);

        $response = $this->get(route('vendors.index', ['search' => 'Alpha']));
        $response->assertOk();
        $response->assertSee('Alpha Brand');
        $response->assertDontSee('Beta Brand');
    }
}
