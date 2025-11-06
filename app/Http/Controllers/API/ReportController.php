<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Post;
use App\Models\Report;
use App\Models\User;
use App\Services\ReportService;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    protected $reportService;

    public function __construct(ReportService $reportService)
    {
        $this->reportService = $reportService;
    }

    /**
     * Report a user
     */
    public function reportUser(Request $request, $userId)
    {
        try {
            $request->validate([
                'reason' => ['required', 'in:' . implode(',', Report::getReasons())],
                'description' => 'nullable|string|max:1000'
            ]);

            $reporter = $request->user();
            $reportedUser = User::findOrFail($userId);

            $result = $this->reportService->reportUser(
                $reporter,
                $reportedUser,
                $request->reason,
                $request->description
            );

            if ($result['success']) {
                return response()->json([
                    'error' => 0,
                    'message' => $result['message'],
                    'data' => [
                        'report_id' => $result['report']->id,
                        'status' => $result['report']->status
                    ]
                ], 201);
            }

            $statusCode = str_contains($result['message'], 'Too many') ? 429 : 400;
            return response()->json([
                'error' => 1,
                'message' => $result['message']
            ], $statusCode);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 1,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Report a post
     */
    public function reportPost(Request $request, $postId)
    {
        try {
            $request->validate([
                'reason' => ['required', 'in:' . implode(',', Report::getReasons())],
                'description' => 'nullable|string|max:1000'
            ]);

            $reporter = $request->user();
            $reportedPost = Post::findOrFail($postId);

            $result = $this->reportService->reportPost(
                $reporter,
                $reportedPost,
                $request->reason,
                $request->description
            );

            if ($result['success']) {
                return response()->json([
                    'error' => 0,
                    'message' => $result['message'],
                    'data' => [
                        'report_id' => $result['report']->id,
                        'status' => $result['report']->status
                    ]
                ], 201);
            }

            $statusCode = str_contains($result['message'], 'Too many') ? 429 : 400;
            return response()->json([
                'error' => 1,
                'message' => $result['message']
            ], $statusCode);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 1,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get user's reports
     */
    public function myReports(Request $request)
    {
        try {
            $user = $request->user();
            
            // Get filters from request
            $filters = [
                'reportable_type' => $request->query('type'), // 'Post' or 'User'
            ];
            
            // Remove null filters
            $filters = array_filter($filters, function($value) {
                return $value !== null && $value !== '';
            });
            
            // Validate type filter if provided
            if (isset($filters['reportable_type']) && !in_array($filters['reportable_type'], ['Post', 'User'])) {
                return response()->json([
                    'error' => 1,
                    'message' => 'Invalid type filter. Must be "Post" or "User".'
                ], 400);
            }
            
            $perPage = $request->query('per_page', 20);
            $reports = $this->reportService->getUserReports($user, $filters, $perPage);

            // Transform reports to a more frontend-friendly format
            $transformedReports = $reports->getCollection()->map(function ($report) {
                // Determine reportable type (Post or User)
                $reportableType = 'Unknown';
                if ($report->reportable_type === \App\Models\Post::class) {
                    $reportableType = 'Post';
                } elseif ($report->reportable_type === \App\Models\User::class) {
                    $reportableType = 'User';
                }

                // Get reportable data
                $reportableData = null;
                if ($report->reportable) {
                    // Load author relation if it's a Post and not already loaded
                    if ($reportableType === 'Post' && !$report->reportable->relationLoaded('author')) {
                        $report->reportable->load('author');
                    }

                    if ($reportableType === 'Post') {
                        $reportableData = [
                            'id' => $report->reportable->id,
                            'content' => $report->reportable->post,
                            'author' => [
                                'id' => $report->reportable->author->id ?? null,
                                'name' => $report->reportable->author->name ?? null,
                                'avatar' => $report->reportable->author->avatar ?? null,
                            ],
                        ];
                    } elseif ($reportableType === 'User') {
                        $reportableData = [
                            'id' => $report->reportable->id,
                            'name' => $report->reportable->name,
                            'email' => $report->reportable->email,
                            'avatar' => $report->reportable->avatar,
                        ];
                    }
                }

                return [
                    'id' => $report->id,
                    'type' => $reportableType, // Simplified: "Post" or "User"
                    'reason' => $report->reason,
                    'description' => $report->description,
                    'status' => $report->status,
                    'reported_item' => $reportableData, // The actual post or user being reported
                    'notes' => $report->admin_notes, // Admin notes
                    'reviewed_at' => $report->reviewed_at?->toISOString(),
                    'created_at' => $report->created_at->toISOString(),
                ];
            });

            return response()->json([
                'error' => 0,
                'data' => [
                    'reports' => $transformedReports,
                    'pagination' => [
                        'current_page' => $reports->currentPage(),
                        'last_page' => $reports->lastPage(),
                        'per_page' => $reports->perPage(),
                        'total' => $reports->total(),
                        'from' => $reports->firstItem(),
                        'to' => $reports->lastItem(),
                        'has_more_pages' => $reports->hasMorePages(),
                        'next_page_url' => $reports->nextPageUrl(),
                        'prev_page_url' => $reports->previousPageUrl(),
                        'first_page_url' => $reports->url(1),
                        'last_page_url' => $reports->url($reports->lastPage()),
                        'path' => $reports->path(),
                        'links' => $reports->linkCollection()->toArray(),
                    ]
                ],
                'filters' => $filters,
                'meta' => [
                    'per_page' => (int) $perPage,
                    'query_string' => $request->getQueryString(),
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 1,
                'message' => $e->getMessage()
            ], 500);
        }
    }
}
