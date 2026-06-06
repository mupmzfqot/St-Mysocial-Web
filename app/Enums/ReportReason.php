<?php

namespace App\Enums;

/**
 * Allowed reasons for reporting a user or a post.
 *
 * The backing value is what the API expects/stores (e.g. "spam").
 * Use label() for a human-friendly string in UIs.
 */
enum ReportReason: string
{
    case Spam = 'spam';
    case Harassment = 'harassment';
    case InappropriateContent = 'inappropriate_content';
    case FakeAccount = 'fake_account';
    case CopyrightViolation = 'copyright_violation';
    case Other = 'other';

    /**
     * Human-friendly label for display.
     */
    public function label(): string
    {
        return match ($this) {
            self::Spam => 'Spam',
            self::Harassment => 'Harassment or bullying',
            self::InappropriateContent => 'Inappropriate content',
            self::FakeAccount => 'Fake account',
            self::CopyrightViolation => 'Copyright violation',
            self::Other => 'Other',
        };
    }

    /**
     * All backing values (e.g. for validation `in:` rules).
     *
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_map(fn (self $reason) => $reason->value, self::cases());
    }

    /**
     * value => label map (e.g. for building a dropdown).
     *
     * @return array<string, string>
     */
    public static function options(): array
    {
        $options = [];
        foreach (self::cases() as $reason) {
            $options[$reason->value] = $reason->label();
        }

        return $options;
    }
}
