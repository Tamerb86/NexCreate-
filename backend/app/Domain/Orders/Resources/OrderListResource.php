<?php

namespace App\Domain\Orders\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderListResource extends JsonResource
{
    /**
     * Transform the resource into an array (lightweight version for lists).
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'price' => (float) $this->price,
            'price_formatted' => number_format($this->price, 2) . ' NOK',
            'status' => $this->status,
            'status_label' => $this->getStatusLabel(),
            'service' => $this->when($this->relationLoaded('service'), function () {
                return [
                    'id' => $this->service->id,
                    'title' => $this->service->title,
                    'slug' => $this->service->slug,
                ];
            }),
            'buyer' => $this->when($this->relationLoaded('buyer'), function () {
                return [
                    'id' => $this->buyer->id,
                    'name' => $this->buyer->name,
                    'avatar' => $this->buyer->avatar,
                ];
            }),
            'creator' => $this->when($this->relationLoaded('creator'), function () {
                return [
                    'id' => $this->creator->id,
                    'name' => $this->creator->name,
                    'avatar' => $this->creator->avatar,
                ];
            }),
            'created_at' => $this->created_at?->toISOString(),
        ];
    }

    /**
     * Get human-readable status label.
     */
    private function getStatusLabel(): string
    {
        return match ($this->status) {
            'pending' => 'Venter på godkjenning',
            'accepted' => 'Godkjent',
            'rejected' => 'Avvist',
            'in_progress' => 'Under arbeid',
            'delivered' => 'Levert',
            'completed' => 'Fullført',
            'cancelled' => 'Kansellert',
            default => ucfirst($this->status),
        };
    }
}
