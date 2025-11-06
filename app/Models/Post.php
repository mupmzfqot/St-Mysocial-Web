<?php

namespace App\Models;

use DateTimeInterface;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;
use Illuminate\Database\Eloquent\SoftDeletes;
use Mews\Purifier\Casts\CleanHtml;
use Mews\Purifier\Casts\CleanHtmlInput;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\Image\Enums\Fit;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Spatie\Permission\Models\Role;

class Post extends Model implements HasMedia
{
    use InteractsWithMedia, SoftDeletes;

    protected $guarded = [];

    protected $appends = ['is_liked', 'commented', 'created_at_for_humans'];

    protected $casts = [
        'content' => CleanHtmlInput::class,
        'created_at' => 'datetime:d F Y h:i A',
    ];

    public function registerMediaConversions(?Media $media = null): void
    {
        $this->addMediaConversion('preview')
            ->fit(Fit::Contain, 300, 300)
            ->nonQueued();
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function group(): HasMany
    {
        return $this->hasMany(PostGroup::class, 'post_id');
    }

    public function userRole(): HasOneThrough
    {
        return $this->hasOneThrough(Role::class, User::class, 'id', 'id', 'user_id');
    }

    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class, 'post_id');
    }

    public function likes(): HasMany
    {
        return $this->hasMany(PostLiked::class, 'post_id');
    }

    public function getCreatedAtForHumansAttribute()
    {
        return $this->created_at->diffForHumans();
    }

    public function scopePublished($query)
    {
        return $query->where('published', true);
    }

    /**
     * Scope to exclude posts from blocked users
     * Usage: Post::excludeBlocked($userId)->get()
     */
    public function scopeExcludeBlocked($query, $userId)
    {
        $blockedIds = \App\Models\UserBlock::where('blocker_id', $userId)->pluck('blocked_id');
        $blockedByIds = \App\Models\UserBlock::where('blocked_id', $userId)->pluck('blocker_id');
        
        $allBlockedIds = $blockedIds->merge($blockedByIds)
            ->unique()
            ->reject(fn($id) => $id == $userId)
            ->toArray();
        
        if (!empty($allBlockedIds)) {
            $query->whereNotIn('user_id', $allBlockedIds);
        }
        
        return $query;
    }

    public function getIsLikedAttribute(): bool
    {
        return $this->likes()->where('user_id', auth()->id())->exists();
    }

    public function getCommentedAttribute(): bool
    {
        return $this->likes()->where('user_id', auth()->id())->exists();
    }

    public function tags(): HasMany
    {
        return $this->hasMany(PostTag::class);
    }

    public function repost(): BelongsTo
    {
        return $this->belongsTo(Post::class, 'repost_id');
    }

    public function reposts(): HasMany
    {
        return $this->hasMany(Post::class, 'repost_id');
    }

    protected function serializeDate(DateTimeInterface $date)
    {
        return $date->timezone(config('app.timezone'));
    }

}
