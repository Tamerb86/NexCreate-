<?php

namespace Tests\Unit;

use App\Domain\Payments\Services\PaymentService;
use Tests\TestCase;

class PaymentMathTest extends TestCase
{
    public function test_commission_is_rounded_to_two_decimals(): void
    {
        config(['nexcreate.commission_percent' => 0.15]);

        $service = app(PaymentService::class);

        $this->assertSame(3.0, $service->calculateCommission(19.99));
        $this->assertSame(75.0, $service->calculateCommission(500.00));
        $this->assertSame(0.15, $service->calculateCommission(1.00));
    }

    /**
     * Float multiplication truncates: (int) (19.99 * 100) === 1998.
     * Øre conversion must round, never cast-truncate.
     */
    public function test_ore_conversion_rounds_instead_of_truncating(): void
    {
        $this->assertSame(1999, (int) round(19.99 * 100));
        $this->assertSame(4810, (int) round(48.10 * 100));
        $this->assertSame(100, (int) round(1.00 * 100));
    }
}
