<?php

namespace App\Domain\Users\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Role extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'display_name',
        'description',
    ];

    /**
     * Role constants
     */
    public const ADMIN = 'admin';
    public const CREATOR = 'creator';
    public const CLIENT = 'client';

    /**
     * Get all users with this role.
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    /**
     * Check if role is admin.
     */
    public function isAdmin(): bool
    {
        return $this->name === self::ADMIN;
    }

    /**
     * Check if role is creator.
     */
    public function isCreator(): bool
    {
        return $this->name === self::CREATOR;
    }

    /**
     * Check if role is client.
     */
    public function isClient(): bool
    {
        return $this->name === self::CLIENT;
    }
}
