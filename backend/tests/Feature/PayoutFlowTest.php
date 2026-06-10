<?php

namespace Tests\Feature;

use App\Domain\Users\Models\User;
use Database\Factories\OrderFactory;
use Database\Factories\PaymentFactory;
use Database\Factories\ServiceFactory;
use Database\Factories\UserFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PayoutFlowTest extends TestCase
{
    use RefreshDatabase;

    private User $creator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->creator = UserFactory::new()->creator()->stripeOnboarded()->create();
    }

    public function test_payout_requires_completed_stripe_onboarding(): void
    {
        $notOnboarded = UserFactory::new()->creator()->create();

        $this->actingAs($notOnboarded, 'sanctum')
            ->postJson('/api/v1/creator/payouts/request', ['amount' => 500])
            ->assertBadRequest()
            ->assertJsonPath('needs_onboarding', true);
    }

    public function test_payout_is_rejected_when_balance_is_insufficient(): void
    {
        // No earnings at all
        $this->actingAs($this->creator, 'sanctum')
            ->postJson('/api/v1/creator/payouts/request', ['amount' => 500])
            ->assertBadRequest()
            ->assertJsonPath('message', 'Insufficient available balance');
    }

    public function test_payout_below_minimum_is_rejected_by_validation(): void
    {
        $this->actingAs($this->creator, 'sanctum')
            ->postJson('/api/v1/creator/payouts/request', ['amount' => 10])
            ->assertUnprocessable();
    }

    public function test_clients_cannot_request_payouts(): void
    {
        $client = UserFactory::new()->create();

        $this->actingAs($client, 'sanctum')
            ->postJson('/api/v1/creator/payouts/request', ['amount' => 500])
            ->assertForbidden();
    }

    public function test_successful_payout_and_pending_payouts_reduce_available_balance(): void
    {
        $this->giveAvailableEarnings(850.00); // creator_earning = 850

        // First payout within balance succeeds
        $this->actingAs($this->creator, 'sanctum')
            ->postJson('/api/v1/creator/payouts/request', ['amount' => 600])
            ->assertCreated();

        $this->assertDatabaseHas('payouts', [
            'creator_id' => $this->creator->id,
            'amount' => 600.00,
            'status' => 'pending',
        ]);

        // Second payout exceeding the remaining 250 must fail —
        // proves pending payouts are subtracted from available balance.
        $this->actingAs($this->creator, 'sanctum')
            ->postJson('/api/v1/creator/payouts/request', ['amount' => 300])
            ->assertBadRequest()
            ->assertJsonPath('message', 'Insufficient available balance');

        // Remaining 250 can still be withdrawn
        $this->actingAs($this->creator, 'sanctum')
            ->postJson('/api/v1/creator/payouts/request', ['amount' => 250])
            ->assertCreated();
    }

    public function test_earnings_not_yet_available_cannot_be_withdrawn(): void
    {
        // Paid, but still inside the payout-delay window
        $this->giveAvailableEarnings(1000.00, available: false);

        $this->actingAs($this->creator, 'sanctum')
            ->postJson('/api/v1/creator/payouts/request', ['amount' => 500])
            ->assertBadRequest()
            ->assertJsonPath('message', 'Insufficient available balance');
    }

    public function test_balance_endpoint_reports_components_correctly(): void
    {
        $this->giveAvailableEarnings(850.00);

        $response = $this->actingAs($this->creator, 'sanctum')
            ->getJson('/api/v1/creator/balance')
            ->assertOk();

        $this->assertEquals(850.00, (float) $response->json('data.available'));
        $this->assertEquals(850.00, (float) $response->json('data.total_earned'));
    }

    /**
     * Create a paid payment for this creator with the given creator_earning.
     */
    private function giveAvailableEarnings(float $earning, bool $available = true): void
    {
        $service = ServiceFactory::new()->create(['user_id' => $this->creator->id]);
        $order = OrderFactory::new()->create([
            'service_id' => $service->id,
            'creator_id' => $this->creator->id,
        ]);

        PaymentFactory::new()->paidAndAvailable()->create([
            'order_id' => $order->id,
            'buyer_id' => $order->buyer_id,
            'creator_id' => $this->creator->id,
            'amount' => $earning + 150,
            'commission' => 150,
            'creator_earning' => $earning,
            'available_at' => $available ? now()->subDay() : now()->addDays(5),
        ]);
    }
}
