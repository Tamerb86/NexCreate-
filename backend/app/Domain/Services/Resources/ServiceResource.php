<?php

namespace App\Domain\Services\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ServiceResource extends JsonResource
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
            'title' => $this->title,
            'slug' => $this->slug,
            'description' => $this->description,
            'price' => (float) $this->price,
            'price_formatted' => number_format($this->price, 2) . ' NOK',
            'delivery_time' => $this->delivery_time,
            'delivery_time_formatted' => $this->delivery_time . ' ' . ($this->delivery_time === 1 ? 'day' : 'days'),
            'revisions' => $this->revisions,
            'status' => $this->status,
            'views_count' => $this->views_count,
            'orders_count' => $this->orders_count,
            'category' => new CategoryResource($this->whenLoaded('category')),
            'images' => ServiceImageResource::collection($this->whenLoaded('images')),
            'primary_image' => $this->when(
                $this->relationLoaded('images'),
                fn() => $this->images->firstWhere('is_primary', true)?->url ?? $this->images->first()?->url
            ),
            'creator' => $this->when($this->relationLoaded('user'), function () {
                return [
                    'id' => $this->user->id,
                    'name' => $this->user->name,
                    'username' => $this->user->username,
                    'avatar' => $this->user->avatar,
                    'bio' => $this->user->bio,
                ];
            }),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
