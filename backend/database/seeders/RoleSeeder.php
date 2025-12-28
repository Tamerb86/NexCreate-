<?php

namespace Database\Seeders;

use App\Domain\Users\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $roles = [
            [
                'name' => Role::ADMIN,
                'display_name' => 'Administrator',
                'description' => 'Full system access and management capabilities.',
            ],
            [
                'name' => Role::CREATOR,
                'display_name' => 'Content Creator',
                'description' => 'UGC content creator who offers services to clients.',
            ],
            [
                'name' => Role::CLIENT,
                'display_name' => 'Client',
                'description' => 'Brand or business looking for UGC content.',
            ],
        ];

        foreach ($roles as $role) {
            Role::updateOrCreate(
                ['name' => $role['name']],
                $role
            );
        }
    }
}
