<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Report extends Model
{
    protected $guarded = [];

    // Report reasons constants
    public const REASON_SPAM = 'spam';
    public const REASON_HARASSMENT = 'harassment';
    public const REASON_INAPPROPRIATE_CONTENT = 'inappropriate_content';
    public const REASON_FAKE_ACCOUNT = 'fake_account';
    public const REASON_COPYRIGHT_VIOLATION = 'copyright_violation';
    public const REASON_OTHER = 'other';

    // Status constants
    public const STATUS_PENDING = 'pending';
    public const STATUS_REVIEWED = 'reviewed';
    public const STATUS_RESOLVED = 'resolved';
    public const STATUS_DISMISSED = 'dismissed';

    protected $casts = [
        'reviewed_at' => 'datetime',
    ];

    /**
     * Get all available report reasons
     */
    public static function getReasons(): array
    {
        return [
            self::REASON_SPAM,
            self::REASON_HARASSMENT,
            self::REASON_INAPPROPRIATE_CONTENT,
            self::REASON_FAKE_ACCOUNT,
            self::REASON_COPYRIGHT_VIOLATION,
            self::REASON_OTHER,
        ];
    }

    /**
     * Get all available report statuses
     */
    public static function getStatuses(): array
    {
        return [
            self::STATUS_PENDING,
            self::STATUS_REVIEWED,
            self::STATUS_RESOLVED,
            self::STATUS_DISMISSED,
        ];
    }

    /**
     * User yang membuat report
     */
    public function reporter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reporter_id');
    }

    /**
     * Polymorphic relationship - bisa report User atau Post
     */
    public function reportable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Admin yang mereview report
     */
    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    /**
     * Scope untuk mendapatkan reports yang pending
     */
    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    /**
     * Scope untuk mendapatkan reports yang sudah di-review
     */
    public function scopeReviewed($query)
    {
        return $query->where('status', '!=', self::STATUS_PENDING);
    }

    /**
     * Scope untuk mendapatkan reports untuk user tertentu
     */
    public function scopeForUser($query, $userId)
    {
        return $query->where('reportable_type', User::class)
            ->where('reportable_id', $userId);
    }

    /**
     * Scope untuk mendapatkan reports untuk post tertentu
     */
    public function scopeForPost($query, $postId)
    {
        return $query->where('reportable_type', Post::class)
            ->where('reportable_id', $postId);
    }
}
