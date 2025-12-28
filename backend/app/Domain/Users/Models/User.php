<?php

namespace App\Domain\Users\Models;

use App\Domain\Orders\Models\Order;
use App\Domain\Payments\Models\Payment;
use App\Domain\Payments\Models\Payout;
use App\Domain\Services\Models\Service;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'username',
        'email',
        'phone',
        'password',
        'avatar',
        'bio',
        'city',
        'country',
        'role_id',
        'stripe_account_id',
        'stripe_onboarding_complete',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'stripe_onboarding_complete' => 'boolean',
        ];
    }

    /**
     * Get the role that the user belongs to.
     */
    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    /**
     * Get the social links for the user.
     */
    public function socialLinks(): HasMany
    {
        return $this->hasMany(UserSocialLink::class);
    }

    /**
     * Get the services created by this user.
     */
    public function services(): HasMany
    {
        return $this->hasMany(Service::class);
    }

    /**
     * Get orders where user is the buyer.
     */
    public function buyerOrders(): HasMany
    {
        return $this->hasMany(Order::class, 'buyer_id');
    }

    /**
     * Get orders where user is the creator (service provider).
     */
    public function creatorOrders(): HasMany
    {
        return $this->hasMany(Order::class, 'creator_id');
    }

    /**
     * Get payments where user is the buyer.
     */
    public function buyerPayments(): HasMany
    {
        return $this->hasMany(Payment::class, 'buyer_id');
    }

    /**
     * Get payments where user is the creator (earnings).
     */
    public function creatorPayments(): HasMany
    {
        return $this->hasMany(Payment::class, 'creator_id');
    }

    /**
     * Get payouts requested by this creator.
     */
    public function payouts(): HasMany
    {
        return $this->hasMany(Payout::class, 'creator_id');
    }

    /**
     * Check if user is admin.
     */
    public function isAdmin(): bool
    {
        return $this->role->name === Role::ADMIN;
    }

    /**
     * Check if user is creator.
     */
    public function isCreator(): bool
    {
        return $this->role->name === Role::CREATOR;
    }

    /**
     * Check if user is client.
     */
    public function isClient(): bool
    {
        return $this->role->name === Role::CLIENT;
    }

    /**
     * Check if user has a specific role.
     */
    public function hasRole(string $roleName): bool
    {
        return $this->role->name === $roleName;
    }

    /**
     * Check if user has Stripe Connect account.
     */
    public function hasStripeAccount(): bool
    {
        return !empty($this->stripe_account_id);
    }

    /**
     * Check if user has completed Stripe onboarding.
     */
    public function hasCompletedStripeOnboarding(): bool
    {
        return $this->stripe_onboarding_complete;
    }

    /**
     * Check if user can receive payouts.
     */
    public function canReceivePayouts(): bool
    {
        return $this->hasStripeAccount() && $this->hasCompletedStripeOnboarding();
    }
}
