<?php

namespace App\Domain\Services\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ServiceListResource extends JsonResource
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
            'title' => $this->title,
            'slug' => $this->slug,
            'price' => (float) $this->price,
            'price_formatted' => number_format($this->price, 2) . ' NOK',
            'delivery_time' => $this->delivery_time,
            'status' => $this->status,
            'category' => $this->when($this->relationLoaded('category'), function () {
                return [
                    'id' => $this->category->id,
                    'name' => $this->category->name,
                    'slug' => $this->category->slug,
                ];
            }),
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
                ];
            }),
        ];
    }
}
