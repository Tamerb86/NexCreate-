<?php

namespace App\Domain\Orders\Resources;

use App\Domain\Services\Resources\ServiceResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderResource extends JsonResource
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
            'price' => (float) $this->price,
            'price_formatted' => number_format($this->price, 2) . ' NOK',
            'requirements' => $this->requirements,
            'status' => $this->status,
            'status_label' => $this->getStatusLabel(),
            'delivery_date' => $this->delivery_date?->toISOString(),
            'service' => $this->when($this->relationLoaded('service'), function () {
                return [
                    'id' => $this->service->id,
                    'title' => $this->service->title,
                    'slug' => $this->service->slug,
                    'delivery_time' => $this->service->delivery_time,
                    'primary_image' => $this->service->images->firstWhere('is_primary', true)?->url 
                        ?? $this->service->images->first()?->url,
                ];
            }),
            'buyer' => $this->when($this->relationLoaded('buyer'), function () {
                return [
                    'id' => $this->buyer->id,
                    'name' => $this->buyer->name,
                    'username' => $this->buyer->username,
                    'avatar' => $this->buyer->avatar,
                ];
            }),
            'creator' => $this->when($this->relationLoaded('creator'), function () {
                return [
                    'id' => $this->creator->id,
                    'name' => $this->creator->name,
                    'username' => $this->creator->username,
                    'avatar' => $this->creator->avatar,
                ];
            }),
            'deliveries' => OrderDeliveryResource::collection($this->whenLoaded('deliveries')),
            'messages' => OrderMessageResource::collection($this->whenLoaded('messages')),
            'messages_count' => $this->whenCounted('messages'),
            'deliveries_count' => $this->whenCounted('deliveries'),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
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
