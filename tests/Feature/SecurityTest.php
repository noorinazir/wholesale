<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\SystemSetting;
use App\Services\EmailSendingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SecurityTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_requires_authentication(): void
    {
        $response = $this->get(route('dashboard'));
        $response->assertRedirect(route('login'));
    }

    public function test_vendors_requires_authentication(): void
    {
        $response = $this->get(route('vendors.index'));
        $response->assertRedirect(route('login'));
    }

    public function test_settings_require_manage_settings_gate(): void
    {
        $user = User::factory()->create([
            'role' => 'staff',
            'is_active' => true,
        ]);
        $user->assignRole('staff');
        $this->actingAs($user);

        $response = $this->get(route('settings.company'));
        $response->assertForbidden();
    }

    public function test_admin_can_access_settings(): void
    {
        $user = User::factory()->create([
            'role' => 'administrator',
            'is_active' => true,
        ]);
        $user->assignRole('administrator');
        $this->actingAs($user);

        $response = $this->get(route('settings.company'));
        $response->assertOk();
    }

    public function test_sending_pause_prevents_emails(): void
    {
        $sendingService = app(EmailSendingService::class);
        $sendingService->pauseSending();

        $this->assertFalse($sendingService->canSend());

        $sendingService->resumeSending();
    }

    public function test_system_setting_encryption(): void
    {
        SystemSetting::set('secret_key', 'my-secret-value', 'test', true);

        $setting = SystemSetting::where('key', 'secret_key')->first();
        $this->assertNotEquals('my-secret-value', $setting->value);
        $this->assertEquals('my-secret-value', SystemSetting::get('secret_key'));
    }

    public function test_csrf_protection_on_post_routes(): void
    {
        $user = User::factory()->create([
            'role' => 'administrator',
            'is_active' => true,
        ]);
        $user->assignRole('administrator');
        $this->actingAs($user);

        $response = $this->post(route('vendors.create'), [
            'brand_name' => 'Test',
        ]);

        $response->assertStatus(302);
    }
}
