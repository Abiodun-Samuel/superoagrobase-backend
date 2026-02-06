<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use App\Enums\RoleEnum;

class RolesSeeder extends Seeder
{
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        foreach (RoleEnum::values() as $roleName) {
            Role::firstOrCreate([
                'name' => $roleName,
                'guard_name' => 'sanctum'
            ]);
        }
    }
}
