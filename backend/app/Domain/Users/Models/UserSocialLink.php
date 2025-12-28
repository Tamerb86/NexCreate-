<?php

namespace App\Domain\Users\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserSocialLink extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'provider',
        'url',
        'username',
        'followers_count',
    ];

    /**
     * Provider constants
     */
    public const INSTAGRAM = 'instagram';
    public const TIKTOK = 'tiktok';
    public const YOUTUBE = 'youtube';
    public const TWITTER = 'twitter';
    public const LINKEDIN = 'linkedin';

    /**
     * Get all available providers.
     */
    public static function providers(): array
    {
        return [
            self::INSTAGRAM,
            self::TIKTOK,
            self::YOUTUBE,
            self::TWITTER,
            self::LINKEDIN,
        ];
    }

    /**
     * Get the user that owns the social link.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
