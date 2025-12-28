<?php

namespace App\Domain\Payments\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PaymentResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'order_id' => $this->order_id,
            'stripe_payment_id' => $this->stripe_payment_id,
            'amount' => (float) $this->amount,
            'commission' => (float) $this->commission,
            'creator_earning' => (float) $this->creator_earning,
            'currency' => strtoupper($this->currency),
            'status' => $this->status,
            'paid_at' => $this->paid_at?->toISOString(),
            'available_at' => $this->available_at?->toISOString(),
            'is_available_for_payout' => $this->isAvailableForPayout(),
            'order' => $this->whenLoaded('order', function () {
                return [
                    'id' => $this->order->id,
                    'status' => $this->order->status,
                ];
            }),
            'buyer' => $this->whenLoaded('buyer', function () {
                return [
                    'id' => $this->buyer->id,
                    'name' => $this->buyer->name,
                    'username' => $this->buyer->username,
                    'avatar' => $this->buyer->avatar,
                ];
            }),
            'creator' => $this->whenLoaded('creator', function () {
                return [
                    'id' => $this->creator->id,
                    'name' => $this->creator->name,
                    'username' => $this->creator->username,
                    'avatar' => $this->creator->avatar,
                ];
            }),
            'created_at' => $this->created_at->toISOString(),
            'updated_at' => $this->updated_at->toISOString(),
        ];
    }
}
