<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
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
            'last_login' => 'datetime:d F Y H:i A'
        ];
    }

    public function getCreatedDateAttribute()
    {
        return $this->created_at?->format('d M Y');
    }

    public function scopeIsActive($query)
    {
        return $query->where('is_active', true);
    }

    public function getAvatarAttribute(): ?string
    {
        return $this->getMedia('avatar')
            ->where('is_verified', true)
            ->first()?->original_url ?? asset('default-avatar.png');
    }

    public function getCoverImageAttribute(): ?string
    {
        return $this->getMedia('cover_image')
            ->where('is_verified', true)
            ->first()?->original_url ?? asset('background.png');
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

    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class, 'user_id', 'id');
    }

}
