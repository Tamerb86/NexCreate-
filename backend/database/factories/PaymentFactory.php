<?php

namespace Database\Factories;

use App\Domain\Orders\Models\Order;
use App\Domain\Payments\Models\Payment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Payment>
 */
class PaymentFactory extends Factory
{
    protected $model = Payment::class;

    public function definition(): array
    {
        $amount = fake()->randomFloat(2, 100, 5000);
        $commission = round($amount * 0.15, 2);

        return [
            'order_id' => OrderFactory::new(),
            'buyer_id' => fn (array $attributes) => Order::find($attributes['order_id'])->buyer_id,
            'creator_id' => fn (array $attributes) => Order::find($attributes['order_id'])->creator_id,
            'amount' => $amount,
            'commission' => $commission,
            'creator_earning' => $amount - $commission,
            'currency' => 'nok',
            'status' => Payment::STATUS_PENDING,
        ];
    }

    /**
     * Paid payment whose earnings are already available for payout.
     */
    public function paidAndAvailable(): static
    {
        return $this->state(fn () => [
            'status' => Payment::STATUS_PAID,
            'stripe_payment_id' => 'pi_' . fake()->unique()->lexify('????????????'),
            'paid_at' => now()->subDays(10),
            'available_at' => now()->subDays(3),
        ]);
    }
}
