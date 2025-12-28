<?php

namespace App\Domain\Payments\Models;

use App\Domain\Orders\Models\Order;
use App\Domain\Users\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'order_id',
        'buyer_id',
        'creator_id',
        'stripe_payment_id',
        'stripe_checkout_session_id',
        'amount',
        'commission',
        'creator_earning',
        'currency',
        'status',
        'paid_at',
        'available_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'amount' => 'decimal:2',
        'commission' => 'decimal:2',
        'creator_earning' => 'decimal:2',
        'paid_at' => 'datetime',
        'available_at' => 'datetime',
    ];

    /**
     * Status constants
     */
    public const STATUS_PENDING = 'pending';
    public const STATUS_PAID = 'paid';
    public const STATUS_REFUNDED = 'refunded';
    public const STATUS_FAILED = 'failed';

    /**
     * Get the order for this payment.
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * Get the buyer of this payment.
     */
    public function buyer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'buyer_id');
    }

    /**
     * Get the creator who receives this payment.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creator_id');
    }

    /**
     * Check if payment is successful.
     */
    public function isPaid(): bool
    {
        return $this->status === self::STATUS_PAID;
    }

    /**
     * Check if payment is available for payout.
     */
    public function isAvailableForPayout(): bool
    {
        return $this->status === self::STATUS_PAID 
            && $this->available_at !== null 
            && $this->available_at->isPast();
    }

    /**
     * Scope a query to only include paid payments.
     */
    public function scopePaid($query)
    {
        return $query->where('status', self::STATUS_PAID);
    }

    /**
     * Scope a query to only include available payments for payout.
     */
    public function scopeAvailableForPayout($query)
    {
        return $query->where('status', self::STATUS_PAID)
            ->whereNotNull('available_at')
            ->where('available_at', '<=', now());
    }

    /**
     * Scope a query to filter by creator.
     */
    public function scopeByCreator($query, int $creatorId)
    {
        return $query->where('creator_id', $creatorId);
    }

    /**
     * Scope a query to filter by buyer.
     */
    public function scopeByBuyer($query, int $buyerId)
    {
        return $query->where('buyer_id', $buyerId);
    }
}
