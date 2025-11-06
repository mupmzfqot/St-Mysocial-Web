<?php

namespace App\Http\Controllers;

use App\Models\Post;
use App\Models\Report;
use App\Models\User;
use App\Services\ReportService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AdminReportController extends Controller
{
    protected $reportService;

    public function __construct(ReportService $reportService)
    {
        $this->reportService = $reportService;
    }

    /**
     * Display a listing of reports
     */
    public function index(Request $request)
    {
        $filters = [
            'status' => $request->query('status'),
            'reason' => $request->query('reason'),
            'reportable_type' => $request->query('type'),
            'date_from' => $request->query('date_from'),
            'date_to' => $request->query('date_to'),
            'search' => $request->query('search'),
        ];

        // Remove null filters
        $filters = array_filter($filters, function($value) {
            return $value !== null && $value !== '';
        });

        $perPage = $request->query('per_page', 20);
        $reports = $this->reportService->getAllReports($filters, $perPage);

        // Transform reports for frontend
        $transformedReports = $reports->getCollection()->map(function ($report) {
            // Determine reportable type (Post or User)
            $reportableType = 'Unknown';
            if ($report->reportable_type === Post::class) {
                $reportableType = 'Post';
            } elseif ($report->reportable_type === User::class) {
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
                    // Load media if not already loaded
                    if (!$report->reportable->relationLoaded('media')) {
                        $report->reportable->load('media');
                    }
                    
                    // Transform media
                    $media = $report->reportable->getMedia('*')->map(function ($item) {
                        if (!$item) return null;
                        
                        $isVideo = str_starts_with($item->mime_type ?? '', 'video/');
                        
                        return [
                            'id' => $item->id ?? null,
                            'filename' => $item->file_name ?? null,
                            'preview_url' => $item->getUrl('preview') ?? null,
                            'original_url' => $item->getUrl() ?? null,
                            'extension' => $item->extension ?? null,
                            'mime_type' => $item->mime_type ?? null,
                            'url' => $isVideo ? url("/stream-video/{$item->file_name}") : ($item->getUrl() ?? null),
                            'is_video' => $isVideo,
                        ];
                    })->filter()->values()->all();
                    
                    $reportableData = [
                        'id' => $report->reportable->id,
                        'content' => $report->reportable->post,
                        'author' => [
                            'id' => $report->reportable->author->id ?? null,
                            'name' => $report->reportable->author->name ?? null,
                            'avatar' => $report->reportable->author->avatar ?? null,
                        ],
                        'media' => $media,
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
                'type' => $reportableType,
                'reason' => $report->reason,
                'description' => $report->description,
                'status' => $report->status,
                'reported_item' => $reportableData,
                'reporter' => [
                    'id' => $report->reporter->id ?? null,
                    'name' => $report->reporter->name ?? null,
                    'email' => $report->reporter->email ?? null,
                    'avatar' => $report->reporter->avatar ?? null,
                ],
                'reviewer' => $report->reviewer ? [
                    'id' => $report->reviewer->id,
                    'name' => $report->reviewer->name,
                ] : null,
                'admin_notes' => $report->admin_notes,
                'reviewed_at' => $report->reviewed_at?->toISOString(),
                'created_at' => $report->created_at->toISOString(),
            ];
        });

        // Get pagination links - Laravel's linkCollection()->toArray() already returns array structure
        // Just convert to indexed array to ensure it's filterable
        $links = array_values($reports->linkCollection()->toArray());

        return Inertia::render('Reports/Index', [
            'reports' => [
                'data' => $transformedReports,
                'links' => $links,
                'current_page' => $reports->currentPage(),
                'last_page' => $reports->lastPage(),
                'per_page' => $reports->perPage(),
                'total' => $reports->total(),
                'from' => $reports->firstItem(),
                'to' => $reports->lastItem(),
            ],
            'filters' => $filters,
            'statuses' => Report::getStatuses(),
            'reasons' => Report::getReasons(),
        ]);
    }

    /**
     * Display the specified report
     */
    public function show($id)
    {
        $report = Report::with(['reporter', 'reportable', 'reviewer'])
            ->findOrFail($id);

        // Load author and media if it's a Post
        if ($report->reportable_type === Post::class && $report->reportable) {
            $report->reportable->load(['author', 'media']);
        }

        // Determine reportable type
        $reportableType = 'Unknown';
        if ($report->reportable_type === Post::class) {
            $reportableType = 'Post';
        } elseif ($report->reportable_type === User::class) {
            $reportableType = 'User';
        }

        // Transform report data
        $reportableData = null;
        if ($report->reportable) {
            if ($reportableType === 'Post') {
                // Transform media
                $media = $report->reportable->getMedia('*')->map(function ($item) {
                    if (!$item) return null;
                    
                    $isVideo = str_starts_with($item->mime_type ?? '', 'video/');
                    
                    return [
                        'id' => $item->id ?? null,
                        'filename' => $item->file_name ?? null,
                        'preview_url' => $item->getUrl('preview') ?? null,
                        'original_url' => $item->getUrl() ?? null,
                        'extension' => $item->extension ?? null,
                        'mime_type' => $item->mime_type ?? null,
                        'url' => $isVideo ? url("/stream-video/{$item->file_name}") : ($item->getUrl() ?? null),
                        'is_video' => $isVideo,
                    ];
                })->filter()->values()->all();
                
                $reportableData = [
                    'id' => $report->reportable->id,
                    'content' => $report->reportable->post,
                    'author' => [
                        'id' => $report->reportable->author->id ?? null,
                        'name' => $report->reportable->author->name ?? null,
                        'avatar' => $report->reportable->author->avatar ?? null,
                    ],
                    'media' => $media,
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

        $transformedReport = [
            'id' => $report->id,
            'type' => $reportableType,
            'reason' => $report->reason,
            'description' => $report->description,
            'status' => $report->status,
            'reported_item' => $reportableData,
            'reporter' => [
                'id' => $report->reporter->id ?? null,
                'name' => $report->reporter->name ?? null,
                'email' => $report->reporter->email ?? null,
                'avatar' => $report->reporter->avatar ?? null,
            ],
            'reviewer' => $report->reviewer ? [
                'id' => $report->reviewer->id,
                'name' => $report->reviewer->name,
            ] : null,
            'admin_notes' => $report->admin_notes,
            'reviewed_at' => $report->reviewed_at?->toISOString(),
            'created_at' => $report->created_at->toISOString(),
        ];

        return Inertia::render('Reports/Show', [
            'report' => $transformedReport,
            'statuses' => Report::getStatuses(),
        ]);
    }

    /**
     * Update report status and admin notes
     */
    public function update(Request $request, $id)
    {
        $request->validate([
            'status' => ['required', 'in:' . implode(',', Report::getStatuses())],
            'admin_notes' => 'nullable|string|max:2000'
        ]);

        $report = Report::findOrFail($id);
        $reviewer = $request->user();

        $result = $this->reportService->updateReportStatus(
            $report,
            $request->status,
            $reviewer,
            $request->admin_notes
        );

        if ($result['success']) {
            return redirect()->route('admin.reports.show', $id)
                ->with('success', $result['message']);
        }

        return redirect()->back()
            ->withErrors(['error' => $result['message']]);
    }
}

