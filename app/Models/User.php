<?php

namespace App\Models;

use DateTimeInterface;
use App\Notifications\CustomVerifyEmail;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Collection;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements MustVerifyEmail, HasMedia
{
    use HasFactory, Notifiable, HasRoles, HasApiTokens, InteractsWithMedia, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */

    protected $guarded = [];

    protected $appends = ['created_date', 'avatar', 'cover_image'];

    public function receivesBroadcastNotificationsOn(): string
    {
        return 'users.'.$this->id;
    }

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
            'created_at' => 'datetime:d F Y H:i A',
            'updated_at' => 'datetime:d F Y H:i A',
            'last_login' => 'datetime:d F Y H:i A',
            'deletion_requested_at' => 'datetime',
            'scheduled_deletion_at' => 'datetime',
        ];
    }

    protected function getNameAttribute($value): string
    {
        return ucwords(strtolower($value));
    }

    public function getCreatedDateAttribute()
    {
        return $this->created_at?->format('d M Y');
    }

    public function scopeIsActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeExceptId($query, $id)
    {
        return $query->whereNot('id', $id);
    }

    public function getAvatarAttribute(): ?string
    {
        return $this->getMedia('avatar')
            ->first()?->original_url ?? asset('default-avatar.webp');
    }

    public function getCoverImageAttribute(): ?string
    {
        return $this->getMedia('cover_image')
            ->first()?->original_url ?? asset('background.webp');
    }

    public function conversations()
    {
        return $this->belongsToMany(Conversation::class, 'conversation_users')
            ->withPivot('role', 'joined_at')
            ->withTimestamps();
    }

    public function messages(): HasMany
    {
        return $this->hasMany(Message::class, 'sender_id');
    }

    public function posts(): HasMany
    {
        return $this->hasMany(Post::class);
    }

    public function likes(): HasMany
    {
        return $this->hasMany(PostLiked::class, 'user_id', 'id');
    }

    public function tags(): HasMany
    {
        return $this->hasMany(PostTag::class, 'user_id', 'id');
    }

    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class, 'user_id', 'id');
    }

    protected function serializeDate(DateTimeInterface $date)
    {
        return $date->timezone(config('app.timezone'));
    }

    public function devices(): HasMany
    {
        return $this->hasMany(UserDevice::class);
    }

    /**
     * Users yang di-block oleh user ini
     */
    public function blockedUsers(): HasMany
    {
        return $this->hasMany(UserBlock::class, 'blocker_id');
    }

    /**
     * Users yang mem-block user ini
     */
    public function blockedByUsers(): HasMany
    {
        return $this->hasMany(UserBlock::class, 'blocked_id');
    }

    /**
     * Reports yang dibuat oleh user ini
     */
    public function reports(): HasMany
    {
        return $this->hasMany(Report::class, 'reporter_id');
    }

    /**
     * Check if user is blocked by another user
     */
    public function isBlockedBy($userId): bool
    {
        return UserBlock::where('blocker_id', $userId)
            ->where('blocked_id', $this->id)
            ->exists();
    }

    /**
     * Check if user has blocked another user
     */
    public function hasBlocked($userId): bool
    {
        return UserBlock::where('blocker_id', $this->id)
            ->where('blocked_id', $userId)
            ->exists();
    }

    /**
     * Get IDs of users that this user has blocked
     */
    public function getBlockedUserIds(): Collection
    {
        return UserBlock::where('blocker_id', $this->id)
            ->pluck('blocked_id');
    }

    /**
     * Get IDs of users that have blocked this user
     */
    public function getBlockedByUserIds(): Collection
    {
        return UserBlock::where('blocked_id', $this->id)
            ->pluck('blocker_id');
    }

    /**
     * Get all blocked user IDs (both directions)
     */
    public function getAllBlockedUserIds(): Collection
    {
        return $this->getBlockedUserIds()
            ->merge($this->getBlockedByUserIds())
            ->unique();
    }

    /**
     * Check if account deletion is requested
     */
    public function isDeletionRequested(): bool
    {
        return $this->account_status === 'deletion_requested';
    }

    /**
     * Check if account can be reactivated
     */
    public function canReactivate(): bool
    {
        return $this->isDeletionRequested() && 
               $this->scheduled_deletion_at && 
               $this->scheduled_deletion_at->isFuture();
    }

    /**
     * Send the email verification notification.
     */
    public function sendEmailVerificationNotification()
    {
        $this->notify(new CustomVerifyEmail);
    }

}
