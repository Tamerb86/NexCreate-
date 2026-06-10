<?php

namespace Database\Factories;

use App\Domain\Orders\Models\Order;
use App\Domain\Services\Models\Service;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Order>
 */
class OrderFactory extends Factory
{
    protected $model = Order::class;

    public function definition(): array
    {
        return [
            'service_id' => ServiceFactory::new(),
            'buyer_id' => UserFactory::new(),
            'creator_id' => fn (array $attributes) => Service::find($attributes['service_id'])->user_id,
            'price' => fn (array $attributes) => Service::find($attributes['service_id'])->price,
            'requirements' => fake()->sentence(),
            'status' => Order::STATUS_PENDING,
        ];
    }
}
