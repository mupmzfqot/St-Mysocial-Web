<?php

namespace App\Models;

use Carbon\Carbon;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Mews\Purifier\Casts\CleanHtml;
use Spatie\Image\Enums\Fit;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class Comment extends Model implements HasMedia
{
    use HasFactory, InteractsWithMedia, SoftDeletes;

    protected $guarded = [];

    protected $appends = ['is_liked'];

    protected $casts = [
        'message' => CleanHtml::class
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    protected $date = ['created_at'];

    public function registerMediaConversions(?Media $media = null): void
    {
        $this->addMediaConversion('preview')
            ->fit(Fit::Contain, 300, 300)
            ->nonQueued();
    }

    // Define an accessor for 'created_at' to return diffForHumans
    public function getCreatedAtAttribute($value): string
    {
        return Carbon::parse($value)->diffForHumans();
    }

    public function post(): BelongsTo
    {
        return $this->belongsTo(Post::class);
    }

    public function likes(): HasMany
    {
        return $this->hasMany(CommentLiked::class);
    }

    public function getIsLikedAttribute(): bool
    {
        return $this->likes()->where('user_id', auth()->id())->exists();
    }

    /**
     * Scope to exclude comments from blocked users
     * Usage: Comment::excludeBlocked($userId)->get()
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

    protected function serializeDate(DateTimeInterface $date)
    {
        return $date->timezone(config('app.timezone'));
    }
}
