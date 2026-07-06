<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class SuperAdminSeeder extends Seeder
{
    public function run(): void
    {
        $role = Role::firstOrCreate(['name' => 'super-admin']);

        $user = User::firstOrCreate(
            ['email' => 'superadmin@gmail.com'],
            [
                'name'     => 'Super Admin',
                'phone'    => null,
                'password' => Hash::make('Test@123'),
                'role_id'  => $role->id,
                'status'   => 1,
            ]
        );

        $user->assignRole($role);
    }
}
