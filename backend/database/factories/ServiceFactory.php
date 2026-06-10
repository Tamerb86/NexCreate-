<?php

namespace Database\Factories;

use App\Domain\Services\Models\Category;
use App\Domain\Services\Models\Service;
use App\Domain\Users\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Service>
 */
class ServiceFactory extends Factory
{
    protected $model = Service::class;

    public function definition(): array
    {
        $title = fake()->unique()->sentence(4);

        return [
            'user_id' => UserFactory::new()->creator(),
            'category_id' => CategoryFactory::new(),
            'title' => $title,
            'slug' => Str::slug($title) . '-' . fake()->unique()->numberBetween(1, 99999),
            'description' => fake()->paragraph(4),
            'price' => fake()->randomFloat(2, 100, 5000),
            'delivery_time' => fake()->numberBetween(1, 14),
            'revisions' => fake()->numberBetween(0, 5),
            'status' => Service::STATUS_ACTIVE,
        ];
    }

    public function draft(): static
    {
        return $this->state(fn () => ['status' => Service::STATUS_DRAFT]);
    }
}
