<?php

namespace App\Services;

use App\Models\Post;
use App\Models\Report;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;

class ReportService
{
    /**
     * Report a user
     */
    public function reportUser(User $reporter, User $reportedUser, string $reason, ?string $description = null): array
    {
        // Prevent self-reporting
        if ($reporter->id === $reportedUser->id) {
            return [
                'success' => false,
                'message' => 'You cannot report yourself'
            ];
        }

        // Rate limiting: max 10 reports per day
        $key = 'report_user_' . $reporter->id;
        if (RateLimiter::tooManyAttempts($key, 10)) {
            return [
                'success' => false,
                'message' => 'Too many reports. Please try again later.'
            ];
        }

        // Check if already reported
        $existingReport = Report::where('reporter_id', $reporter->id)
            ->where('reportable_type', User::class)
            ->where('reportable_id', $reportedUser->id)
            ->first();

        if ($existingReport) {
            return [
                'success' => false,
                'message' => 'You have already reported this user'
            ];
        }

        // Validate reason
        if (!in_array($reason, Report::getReasons())) {
            return [
                'success' => false,
                'message' => 'Invalid report reason'
            ];
        }

        DB::beginTransaction();
        try {
            // Create report
            $report = Report::create([
                'reporter_id' => $reporter->id,
                'reportable_type' => User::class,
                'reportable_id' => $reportedUser->id,
                'reason' => $reason,
                'description' => $description,
                'status' => Report::STATUS_PENDING
            ]);

            RateLimiter::hit($key, 86400); // 24 hours

            DB::commit();

            return [
                'success' => true,
                'message' => 'User reported successfully. Our team will review it.',
                'report' => $report
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to report user', [
                'reporter_id' => $reporter->id,
                'reported_user_id' => $reportedUser->id,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => 'Failed to submit report. Please try again later.'
            ];
        }
    }

    /**
     * Report a post
     */
    public function reportPost(User $reporter, Post $reportedPost, string $reason, ?string $description = null): array
    {
        // Rate limiting: max 10 reports per day
        $key = 'report_post_' . $reporter->id;
        if (RateLimiter::tooManyAttempts($key, 10)) {
            return [
                'success' => false,
                'message' => 'Too many reports. Please try again later.'
            ];
        }

        // Check if already reported
        $existingReport = Report::where('reporter_id', $reporter->id)
            ->where('reportable_type', Post::class)
            ->where('reportable_id', $reportedPost->id)
            ->first();

        if ($existingReport) {
            return [
                'success' => false,
                'message' => 'You have already reported this post'
            ];
        }

        // Validate reason
        if (!in_array($reason, Report::getReasons())) {
            return [
                'success' => false,
                'message' => 'Invalid report reason'
            ];
        }

        DB::beginTransaction();
        try {
            // Create report
            $report = Report::create([
                'reporter_id' => $reporter->id,
                'reportable_type' => Post::class,
                'reportable_id' => $reportedPost->id,
                'reason' => $reason,
                'description' => $description,
                'status' => Report::STATUS_PENDING
            ]);

            RateLimiter::hit($key, 86400); // 24 hours

            DB::commit();

            return [
                'success' => true,
                'message' => 'Post reported successfully. Our team will review it.',
                'report' => $report
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to report post', [
                'reporter_id' => $reporter->id,
                'post_id' => $reportedPost->id,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => 'Failed to submit report. Please try again later.'
            ];
        }
    }

    /**
     * Get user's reports
     */
    public function getUserReports(User $user, array $filters = [], int $perPage = 20)
    {
        $query = Report::where('reporter_id', $user->id)
            ->with(['reportable', 'reviewer']);

        // Apply filters
        if (isset($filters['reportable_type'])) {
            // Map 'Post' or 'User' string to full class name
            if ($filters['reportable_type'] === 'Post') {
                $query->where('reportable_type', Post::class);
            } elseif ($filters['reportable_type'] === 'User') {
                $query->where('reportable_type', User::class);
            }
        }

        $reports = $query->orderBy('created_at', 'desc')
            ->paginate($perPage);

        // Eager load author and media for Post reports
        $postReports = $reports->getCollection()->filter(function ($report) {
            return $report->reportable_type === Post::class && $report->reportable;
        });

        if ($postReports->isNotEmpty()) {
            $postIds = $postReports->pluck('reportable_id')->unique();
            // Eager load author and media for posts
            $posts = Post::whereIn('id', $postIds)->with(['author', 'media'])->get()->keyBy('id');
            
            // Attach loaded posts back to reports
            $reports->getCollection()->each(function ($report) use ($posts) {
                if ($report->reportable_type === Post::class && isset($posts[$report->reportable_id])) {
                    $report->setRelation('reportable', $posts[$report->reportable_id]);
                }
            });
        }

        return $reports;
    }

    /**
     * Get all reports (for admin)
     */
    public function getAllReports(array $filters = [], int $perPage = 20)
    {
        $reports = Report::with(['reporter', 'reportable', 'reviewer'])
            ->when(isset($filters['status']), function($query) use ($filters) {
                $query->where('status', $filters['status']);
            })
            ->when(isset($filters['reason']), function($query) use ($filters) {
                $query->where('reason', $filters['reason']);
            })
            ->when(isset($filters['reportable_type']), function($query) use ($filters) {
                // Map 'Post' or 'User' string to full class name
                if ($filters['reportable_type'] === 'Post') {
                    $query->where('reportable_type', Post::class);
                } elseif ($filters['reportable_type'] === 'User') {
                    $query->where('reportable_type', User::class);
                }
            })
            ->when(isset($filters['date_from']), function($query) use ($filters) {
                $query->whereDate('created_at', '>=', $filters['date_from']);
            })
            ->when(isset($filters['date_to']), function($query) use ($filters) {
                $query->whereDate('created_at', '<=', $filters['date_to']);
            })
            ->when(isset($filters['search']), function($query) use ($filters) {
                $search = $filters['search'];
                $query->where(function($q) use ($search) {
                    $q->where('description', 'like', '%' . $search . '%')
                      ->orWhere('reason', 'like', '%' . $search . '%')
                      ->orWhereHas('reporter', function($reporterQuery) use ($search) {
                          $reporterQuery->where('name', 'like', '%' . $search . '%')
                                       ->orWhere('email', 'like', '%' . $search . '%');
                      });
                });
            })
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);

        // Eager load author and media for Post reports
        $postReports = $reports->getCollection()->filter(function ($report) {
            return $report->reportable_type === Post::class && $report->reportable;
        });

        if ($postReports->isNotEmpty()) {
            $postIds = $postReports->pluck('reportable_id')->unique();
            // Eager load author and media for posts
            $posts = Post::whereIn('id', $postIds)->with(['author', 'media'])->get()->keyBy('id');
            
            // Attach loaded posts back to reports
            $reports->getCollection()->each(function ($report) use ($posts) {
                if ($report->reportable_type === Post::class && isset($posts[$report->reportable_id])) {
                    $report->setRelation('reportable', $posts[$report->reportable_id]);
                }
            });
        }

        return $reports;
    }

    /**
     * Update report status (for admin)
     */
    public function updateReportStatus(Report $report, string $status, User $reviewer, ?string $adminNotes = null): array
    {
        if (!in_array($status, Report::getStatuses())) {
            return [
                'success' => false,
                'message' => 'Invalid status'
            ];
        }

        DB::beginTransaction();
        try {
            $report->status = $status;
            $report->reviewed_by = $reviewer->id;
            $report->reviewed_at = now();
            if ($adminNotes) {
                $report->admin_notes = $adminNotes;
            }
            $report->save();

            DB::commit();

            return [
                'success' => true,
                'message' => 'Report status updated successfully',
                'report' => $report->fresh()
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to update report status', [
                'report_id' => $report->id,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => 'Failed to update report status'
            ];
        }
    }
}

