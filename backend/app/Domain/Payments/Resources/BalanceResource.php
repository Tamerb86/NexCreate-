<?php

namespace App\Domain\Payments\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BalanceResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'available' => (float) $this->resource['available'],
            'pending' => (float) $this->resource['pending'],
            'total_earned' => (float) $this->resource['total_earned'],
            'total_withdrawn' => (float) $this->resource['total_withdrawn'],
            'pending_payouts' => (float) $this->resource['pending_payouts'],
            'currency' => 'NOK',
            'can_withdraw' => $this->resource['available'] >= config('nexcreate.minimum_payout'),
            'minimum_payout' => (float) config('nexcreate.minimum_payout'),
        ];
    }
}
