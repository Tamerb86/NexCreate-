<?php

namespace Database\Seeders;

use App\Domain\Users\Models\User;
use App\Domain\Users\Models\Role;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get admin role
        $adminRole = Role::where('name', Role::ADMIN)->first();

        if (!$adminRole) {
            $this->command->error('Admin role not found. Please run RoleSeeder first.');
            return;
        }

        // Create default admin user
        User::updateOrCreate(
            ['email' => 'admin@nexcreate.no'],
            [
                'name' => 'Admin',
                'username' => 'admin',
                'email' => 'admin@nexcreate.no',
                'password' => Hash::make('admin123'),
                'role_id' => $adminRole->id,
                'email_verified_at' => now(),
            ]
        );

        $this->command->info('Default admin user created: admin@nexcreate.no / admin123');
    }
}
