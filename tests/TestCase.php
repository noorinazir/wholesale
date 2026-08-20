<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        if (Schema::hasTable('roles')) {
            foreach (['administrator', 'manager', 'staff', 'viewer'] as $roleName) {
                Role::firstOrCreate(['name' => $roleName, 'guard_name' => 'web']);
            }

            $permissions = [
                'manage-settings', 'manage-vendors', 'manage-campaigns', 'manage-emails', 'manage-finance', 'manage-products',
                'view-vendors', 'view-campaigns', 'view-emails', 'view-finance', 'view-products', 'view-reports',
            ];

            foreach ($permissions as $permission) {
                Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
            }

            $adminRole = Role::where('name', 'administrator')->first();
            $managerRole = Role::where('name', 'manager')->first();
            $staffRole = Role::where('name', 'staff')->first();
            $viewerRole = Role::where('name', 'viewer')->first();

            $adminRole?->syncPermissions($permissions);
            $managerRole?->syncPermissions([
                'manage-vendors', 'manage-campaigns', 'manage-emails', 'manage-finance', 'manage-products',
                'view-vendors', 'view-campaigns', 'view-emails', 'view-finance', 'view-products', 'view-reports',
            ]);
            $staffRole?->syncPermissions([
                'manage-vendors', 'manage-products', 'manage-emails',
                'view-vendors', 'view-campaigns', 'view-emails', 'view-products', 'view-reports',
            ]);
            $viewerRole?->syncPermissions([
                'view-vendors', 'view-campaigns', 'view-emails', 'view-finance', 'view-products', 'view-reports',
            ]);
        }
    }
}
