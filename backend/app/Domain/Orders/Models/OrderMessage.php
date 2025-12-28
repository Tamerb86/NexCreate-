<?php

namespace App\Domain\Orders\Models;

use App\Domain\Users\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderMessage extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'order_id',
        'sender_id',
        'message',
        'attachment',
    ];

    /**
     * Get the order this message belongs to.
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * Get the sender of this message.
     */
    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    /**
     * Check if message has an attachment.
     */
    public function hasAttachment(): bool
    {
        return !empty($this->attachment);
    }

    /**
     * Scope a query to order by oldest first.
     */
    public function scopeOldestFirst($query)
    {
        return $query->orderBy('created_at', 'asc');
    }

    /**
     * Scope a query to order by newest first.
     */
    public function scopeNewestFirst($query)
    {
        return $query->orderBy('created_at', 'desc');
    }
}
