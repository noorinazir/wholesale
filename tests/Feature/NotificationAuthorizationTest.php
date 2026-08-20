<?php

namespace Tests\Feature;

use App\Models\Notification;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotificationAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_cannot_mark_another_users_notification_as_read(): void
    {
        $owner = User::factory()->create(['role' => 'staff']);
        $owner->assignRole('staff');

        $attacker = User::factory()->create(['role' => 'staff']);
        $attacker->assignRole('staff');

        $notification = Notification::create([
            'user_id' => $owner->id,
            'type' => 'info',
            'title' => 'Test',
            'message' => 'Hello',
        ]);

        $this->actingAs($attacker);

        $response = $this->post(route('notifications.read', $notification->id));

        $response->assertForbidden();
        $this->assertNull($notification->fresh()->read_at);
    }

    public function test_user_can_mark_own_notification_as_read(): void
    {
        $user = User::factory()->create(['role' => 'staff']);
        $user->assignRole('staff');

        $notification = Notification::create([
            'user_id' => $user->id,
            'type' => 'info',
            'title' => 'Test',
            'message' => 'Hello',
        ]);

        $this->actingAs($user);

        $response = $this->post(route('notifications.read', $notification->id));

        $response->assertRedirect();
        $this->assertNotNull($notification->fresh()->read_at);
    }
}
