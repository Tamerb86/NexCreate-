<?php

namespace App\Domain\Payments\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PayoutResource extends JsonResource
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
            'amount' => (float) $this->amount,
            'currency' => 'NOK',
            'status' => $this->status,
            'stripe_payout_id' => $this->stripe_payout_id,
            'notes' => $this->notes,
            'failure_reason' => $this->when($this->status === 'failed', $this->failure_reason),
            'processed_at' => $this->processed_at?->toISOString(),
            'can_be_cancelled' => $this->canBeCancelled(),
            'creator' => $this->whenLoaded('creator', function () {
                return [
                    'id' => $this->creator->id,
                    'name' => $this->creator->name,
                    'username' => $this->creator->username,
                ];
            }),
            'created_at' => $this->created_at->toISOString(),
            'updated_at' => $this->updated_at->toISOString(),
        ];
    }
}
