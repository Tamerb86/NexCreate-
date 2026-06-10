<?php

namespace Tests\Feature;

use App\Domain\Orders\Models\Order;
use App\Domain\Services\Models\Service;
use App\Domain\Users\Models\User;
use Database\Factories\ServiceFactory;
use Database\Factories\UserFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderLifecycleTest extends TestCase
{
    use RefreshDatabase;

    private User $creator;
    private User $buyer;
    private Service $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->creator = UserFactory::new()->creator()->create();
        $this->buyer = UserFactory::new()->create();
        $this->service = ServiceFactory::new()->create([
            'user_id' => $this->creator->id,
            'price' => 1000.00,
        ]);
    }

    public function test_full_order_lifecycle_from_order_to_completion(): void
    {
        // Buyer places the order
        $response = $this->actingAs($this->buyer, 'sanctum')
            ->postJson('/api/v1/orders', [
                'service_id' => $this->service->id,
                'requirements' => 'One vertical video for TikTok, 30 seconds.',
            ]);

        $response->assertCreated();
        $orderId = $response->json('data.id');

        $this->assertDatabaseHas('orders', [
            'id' => $orderId,
            'buyer_id' => $this->buyer->id,
            'creator_id' => $this->creator->id,
            'price' => 1000.00,
            'status' => Order::STATUS_PENDING,
        ]);

        // Creator accepts, then starts work
        $this->actingAs($this->creator, 'sanctum')
            ->patchJson("/api/v1/orders/{$orderId}/status", ['status' => Order::STATUS_ACCEPTED])
            ->assertOk();

        $this->actingAs($this->creator, 'sanctum')
            ->patchJson("/api/v1/orders/{$orderId}/status", ['status' => Order::STATUS_IN_PROGRESS])
            ->assertOk();

        // Creator delivers
        $this->actingAs($this->creator, 'sanctum')
            ->postJson("/api/v1/orders/{$orderId}/deliveries", [
                'file_url' => 'https://cdn.example.com/delivery/video-final.mp4',
                'message' => 'Final cut attached.',
            ])
            ->assertCreated();

        $this->assertDatabaseHas('orders', ['id' => $orderId, 'status' => Order::STATUS_DELIVERED]);

        // Buyer marks completed
        $this->actingAs($this->buyer, 'sanctum')
            ->patchJson("/api/v1/orders/{$orderId}/status", ['status' => Order::STATUS_COMPLETED])
            ->assertOk();

        $this->assertDatabaseHas('orders', ['id' => $orderId, 'status' => Order::STATUS_COMPLETED]);
    }

    public function test_buyer_cannot_accept_their_own_order(): void
    {
        $order = $this->placeOrder();

        $this->actingAs($this->buyer, 'sanctum')
            ->patchJson("/api/v1/orders/{$order}/status", ['status' => Order::STATUS_ACCEPTED])
            ->assertUnprocessable();
    }

    public function test_creator_cannot_order_their_own_service(): void
    {
        $this->actingAs($this->creator, 'sanctum')
            ->postJson('/api/v1/orders', ['service_id' => $this->service->id])
            ->assertBadRequest();
    }

    public function test_inactive_service_cannot_be_ordered(): void
    {
        $this->service->update(['status' => Service::STATUS_PAUSED]);

        $this->actingAs($this->buyer, 'sanctum')
            ->postJson('/api/v1/orders', ['service_id' => $this->service->id])
            ->assertBadRequest();
    }

    public function test_third_party_cannot_view_or_message_the_order(): void
    {
        $order = $this->placeOrder();
        $stranger = UserFactory::new()->create();

        $this->actingAs($stranger, 'sanctum')
            ->getJson("/api/v1/orders/{$order}")
            ->assertForbidden();

        $this->actingAs($stranger, 'sanctum')
            ->postJson("/api/v1/orders/{$order}/deliveries", [
                'file_url' => 'https://evil.example.com/x.mp4',
            ])
            ->assertForbidden();
    }

    public function test_completed_order_is_a_final_state(): void
    {
        $order = $this->placeOrder(Order::STATUS_COMPLETED);

        $this->actingAs($this->creator, 'sanctum')
            ->patchJson("/api/v1/orders/{$order}/status", ['status' => Order::STATUS_IN_PROGRESS])
            ->assertUnprocessable();
    }

    public function test_delivery_is_rejected_for_pending_order(): void
    {
        $order = $this->placeOrder(); // still pending — not accepted yet

        $this->actingAs($this->creator, 'sanctum')
            ->postJson("/api/v1/orders/{$order}/deliveries", [
                'file_url' => 'https://cdn.example.com/too-early.mp4',
            ])
            ->assertBadRequest();
    }

    private function placeOrder(string $status = Order::STATUS_PENDING): int
    {
        $order = Order::create([
            'service_id' => $this->service->id,
            'buyer_id' => $this->buyer->id,
            'creator_id' => $this->creator->id,
            'price' => $this->service->price,
            'status' => $status,
        ]);

        return $order->id;
    }
}
