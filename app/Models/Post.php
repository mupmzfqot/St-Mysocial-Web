<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\Image\Enums\Fit;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Spatie\Permission\Models\Role;

class Post extends Model implements HasMedia
{
    use HasFactory, InteractsWithMedia;

    protected $guarded = [];

    protected $appends = ['is_liked', 'commented'];

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

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime:d F Y H:i A',
        ];
    }

    public function scopePublished($query)
    {
        return $query->where('published', true);
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

}
