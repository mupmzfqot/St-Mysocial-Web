<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\AccountDeletionService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Carbon\Carbon;

class AdminAccountRemovalController extends Controller
{
    protected $deletionService;

    public function __construct(AccountDeletionService $deletionService)
    {
        $this->deletionService = $deletionService;
    }

    /**
     * Display a listing of users with deletion requests
     */
    public function index(Request $request)
    {
        $searchTerm = $request->query('search');
        $statusFilter = $request->query('status'); // 'deleted', 'deletion_requested', 'scheduled'

        $users = User::where(function($query) {
                $query->whereNotNull('deleted_at')
                      ->orWhereNotNull('deletion_requested_at')
                      ->orWhereNotNull('scheduled_deletion_at');
            })
            ->when($searchTerm, function($query, $search) {
                $query->where(function($q) use ($search) {
                    $q->where('name', 'like', '%' . $search . '%')
                      ->orWhere('email', 'like', '%' . $search . '%');
                });
            })
            ->when($statusFilter === 'deleted', function($query) {
                $query->whereNotNull('deleted_at');
            })
            ->when($statusFilter === 'deletion_requested', function($query) {
                $query->whereNotNull('deletion_requested_at')
                      ->whereNull('deleted_at');
            })
            ->when($statusFilter === 'scheduled', function($query) {
                $query->whereNotNull('scheduled_deletion_at')
                      ->whereNull('deleted_at');
            })
            ->orderBy('deletion_requested_at', 'desc')
            ->orderBy('deleted_at', 'desc')
            ->paginate($request->query('per_page', 20));

        // Transform users for frontend
        $transformedUsers = $users->getCollection()->map(function ($user) {
            return [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'avatar' => $user->avatar,
                'deleted_at' => $user->deleted_at ? Carbon::parse($user->deleted_at)->toISOString() : null,
                'deletion_requested_at' => $user->deletion_requested_at ? Carbon::parse($user->deletion_requested_at)->toISOString() : null,
                'scheduled_deletion_at' => $user->scheduled_deletion_at ? Carbon::parse($user->scheduled_deletion_at)->toISOString() : null,
                'deletion_reason' => $user->deletion_reason,
                'account_status' => $user->account_status,
                'can_reactivate' => $user->canReactivate(),
            ];
        });

        // Format pagination links
        $paginationLinks = $users->linkCollection()->map(function ($link) {
            return [
                'url' => $link['url'],
                'label' => $link['label'],
                'active' => $link['active'],
            ];
        })->values()->all();

        return Inertia::render('AccountRemoval/Index', [
            'users' => $transformedUsers,
            'pagination' => [
                'current_page' => $users->currentPage(),
                'last_page' => $users->lastPage(),
                'per_page' => $users->perPage(),
                'total' => $users->total(),
                'from' => $users->firstItem(),
                'to' => $users->lastItem(),
                'links' => $paginationLinks,
            ],
            'filters' => [
                'search' => $searchTerm,
                'status' => $statusFilter,
            ],
        ]);
    }

    /**
     * Reactivate a user account
     */
    public function reactivate(Request $request, $userId)
    {
        $user = User::findOrFail($userId);

        // Check if user can be reactivated
        if (!$user->isDeletionRequested() && !$user->deleted_at) {
            return back()->withErrors(['error' => 'User does not have an active deletion request']);
        }

        $success = $this->deletionService->reactivateAccount($user, true);

        if ($success) {
            return back()->with('success', 'Account reactivated successfully. All data has been restored.');
        }

        return back()->withErrors(['error' => 'Failed to reactivate account']);
    }

    /**
     * Permanently delete a user account
     */
    public function permanentDelete(Request $request, $userId)
    {
        $request->validate([
            'confirm' => 'required|accepted'
        ], [
            'confirm.accepted' => 'You must confirm the permanent deletion'
        ]);

        $user = User::findOrFail($userId);

        // Prevent deleting yourself
        if ($user->id === $request->user()->id) {
            return back()->withErrors(['error' => 'You cannot permanently delete your own account']);
        }

        $success = $this->deletionService->permanentDeleteUser($user);

        if ($success) {
            return back()->with('success', 'User account permanently deleted. This action cannot be undone.');
        }

        return back()->withErrors(['error' => 'Failed to permanently delete user account']);
    }
}

