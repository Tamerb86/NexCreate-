<?php

namespace Database\Factories;

use App\Domain\Users\Models\Role;
use App\Domain\Users\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    protected $model = User::class;

    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'username' => fake()->unique()->userName(),
            'email' => fake()->unique()->safeEmail(),
            'password' => Hash::make('password'),
            'role_id' => fn () => $this->roleId(Role::CLIENT),
        ];
    }

    public function creator(): static
    {
        return $this->state(fn () => ['role_id' => $this->roleId(Role::CREATOR)]);
    }

    public function admin(): static
    {
        return $this->state(fn () => ['role_id' => $this->roleId(Role::ADMIN)]);
    }

    /**
     * Creator with completed Stripe Connect onboarding.
     */
    public function stripeOnboarded(): static
    {
        return $this->state(fn () => [
            'stripe_account_id' => 'acct_' . fake()->unique()->lexify('????????????'),
            'stripe_onboarding_complete' => true,
        ]);
    }

    protected function roleId(string $name): int
    {
        return Role::firstOrCreate(
            ['name' => $name],
            ['display_name' => ucfirst($name)]
        )->id;
    }
}
