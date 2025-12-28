<?php

namespace App\Domain\Orders\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderMessageResource extends JsonResource
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
            'message' => $this->message,
            'attachment' => $this->attachment,
            'has_attachment' => !empty($this->attachment),
            'sender' => $this->when($this->relationLoaded('sender'), function () {
                return [
                    'id' => $this->sender->id,
                    'name' => $this->sender->name,
                    'username' => $this->sender->username,
                    'avatar' => $this->sender->avatar,
                ];
            }),
            'is_mine' => auth()->check() && $this->sender_id === auth()->id(),
            'created_at' => $this->created_at?->toISOString(),
            'created_at_human' => $this->created_at?->diffForHumans(),
        ];
    }
}
