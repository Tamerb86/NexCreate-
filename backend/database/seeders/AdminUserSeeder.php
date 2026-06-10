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

        $email = config('nexcreate.admin_email');
        $password = config('nexcreate.admin_password');

        // No hardcoded fallback: a default password in a seeder is a backdoor
        // the moment it runs in a deployed environment.
        if (!$password) {
            $this->command->warn('ADMIN_PASSWORD is not set — skipping admin user creation.');
            $this->command->warn('Set ADMIN_EMAIL and ADMIN_PASSWORD in .env and re-run: php artisan db:seed --class=AdminUserSeeder');
            return;
        }

        User::updateOrCreate(
            ['email' => $email],
            [
                'name' => 'Admin',
                'username' => 'admin',
                'email' => $email,
                'password' => Hash::make($password),
                'role_id' => $adminRole->id,
                'email_verified_at' => now(),
            ]
        );

        $this->command->info("Admin user created: {$email}");
    }
}
