<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\SystemSetting;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $roles = ['administrator', 'manager', 'staff', 'viewer'];
        foreach ($roles as $role) {
            Role::firstOrCreate(['name' => $role]);
        }

        $admin = User::firstOrCreate(
            ['email' => 'admin@wholesale.com'],
            [
                'name' => 'Administrator',
                'password' => Hash::make('password'),
                'is_active' => true,
                'role' => 'administrator',
            ]
        );
        $admin->assignRole('administrator');

        SystemSetting::firstOrCreate(['key' => 'sending_paused'], ['value' => '0', 'group' => 'sending']);
        SystemSetting::firstOrCreate(['key' => 'daily_email_limit'], ['value' => '50', 'group' => 'sending']);
        SystemSetting::firstOrCreate(['key' => 'hourly_email_limit'], ['value' => '10', 'group' => 'sending']);
        SystemSetting::firstOrCreate(['key' => 'delay_type'], ['value' => 'random', 'group' => 'sending']);
        SystemSetting::firstOrCreate(['key' => 'min_delay_seconds'], ['value' => '60', 'group' => 'sending']);
        SystemSetting::firstOrCreate(['key' => 'max_delay_seconds'], ['value' => '180', 'group' => 'sending']);
        SystemSetting::firstOrCreate(['key' => 'sending_start_time'], ['value' => '09:00', 'group' => 'sending']);
        SystemSetting::firstOrCreate(['key' => 'sending_end_time'], ['value' => '17:00', 'group' => 'sending']);
        SystemSetting::firstOrCreate(['key' => 'sending_timezone'], ['value' => config('app.timezone'), 'group' => 'sending']);
        SystemSetting::firstOrCreate(['key' => 'allowed_weekdays'], ['value' => '["1","2","3","4","5"]', 'group' => 'sending']);
        SystemSetting::firstOrCreate(['key' => 'include_opt_out'], ['value' => '1', 'group' => 'general']);
        SystemSetting::firstOrCreate(['key' => 'enable_follow_ups'], ['value' => '0', 'group' => 'general']);
    }
}
