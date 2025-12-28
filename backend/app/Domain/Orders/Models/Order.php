<?php

namespace App\Domain\Orders\Models;

use App\Domain\Users\Models\User;
use App\Domain\Services\Models\Service;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'service_id',
        'buyer_id',
        'creator_id',
        'price',
        'requirements',
        'status',
        'delivery_date',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'price' => 'decimal:2',
        'delivery_date' => 'datetime',
    ];

    /**
     * Status constants
     */
    public const STATUS_PENDING = 'pending';
    public const STATUS_ACCEPTED = 'accepted';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_IN_PROGRESS = 'in_progress';
    public const STATUS_DELIVERED = 'delivered';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_CANCELLED = 'cancelled';

    /**
     * Get all available statuses.
     */
    public static function statuses(): array
    {
        return [
            self::STATUS_PENDING,
            self::STATUS_ACCEPTED,
            self::STATUS_REJECTED,
            self::STATUS_IN_PROGRESS,
            self::STATUS_DELIVERED,
            self::STATUS_COMPLETED,
            self::STATUS_CANCELLED,
        ];
    }

    /**
     * Get the service for this order.
     */
    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    /**
     * Get the buyer (client) of this order.
     */
    public function buyer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'buyer_id');
    }

    /**
     * Get the creator (service provider) of this order.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creator_id');
    }

    /**
     * Get all messages for this order.
     */
    public function messages(): HasMany
    {
        return $this->hasMany(OrderMessage::class)->orderBy('created_at', 'asc');
    }

    /**
     * Get all deliveries for this order.
     */
    public function deliveries(): HasMany
    {
        return $this->hasMany(OrderDelivery::class)->orderBy('created_at', 'desc');
    }

    /**
     * Check if user is a party in this order.
     */
    public function isParty(int $userId): bool
    {
        return $this->buyer_id === $userId || $this->creator_id === $userId;
    }

    /**
     * Check if user is the buyer.
     */
    public function isBuyer(int $userId): bool
    {
        return $this->buyer_id === $userId;
    }

    /**
     * Check if user is the creator.
     */
    public function isCreator(int $userId): bool
    {
        return $this->creator_id === $userId;
    }

    /**
     * Scope a query to filter by buyer.
     */
    public function scopeByBuyer($query, int $userId)
    {
        return $query->where('buyer_id', $userId);
    }

    /**
     * Scope a query to filter by creator.
     */
    public function scopeByCreator($query, int $userId)
    {
        return $query->where('creator_id', $userId);
    }

    /**
     * Scope a query to filter by status.
     */
    public function scopeWithStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Check if order can be cancelled.
     */
    public function canBeCancelled(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    /**
     * Check if order is active (not completed, cancelled, or rejected).
     */
    public function isActive(): bool
    {
        return !in_array($this->status, [
            self::STATUS_COMPLETED,
            self::STATUS_CANCELLED,
            self::STATUS_REJECTED,
        ]);
    }
}
