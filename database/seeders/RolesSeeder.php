<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use App\Enums\RoleEnum;

class RolesSeeder extends Seeder
{
    public function run(): void
    {
        foreach (RoleEnum::values() as $roleName) {
            Role::firstOrCreate(['name' => $roleName]);
        }
    }
}
