<?php

namespace App\Http\Controllers;

use App\Models\Post;
use App\Models\Report;
use App\Models\User;
use App\Services\AccountDeletionService;
use App\Services\BlockService;
use App\Services\ReportService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;

class UserManagementController extends Controller
{
    protected $deletionService;
    protected $blockService;
    protected $reportService;

    public function __construct(
        AccountDeletionService $deletionService,
        BlockService $blockService,
        ReportService $reportService
    ) {
        $this->deletionService = $deletionService;
        $this->blockService = $blockService;
        $this->reportService = $reportService;
    }

    /**
     * Request account deletion (Web)
     */
    public function requestDeletion(Request $request)
    {
        $request->validate([
            'password' => 'required',
            'reason' => 'nullable|string|max:500'
        ]);

        $user = Auth::user();

        // Verify password
        if (!Hash::check($request->password, $user->password)) {
            return back()->withErrors(['password' => 'Wrong password']);
        }

        // Check if already requested
        if ($user->isDeletionRequested()) {
            return back()->withErrors(['error' => 'Account deletion already requested']);
        }

        $success = $this->deletionService->requestDeletion($user, $request->reason);

        if ($success) {
            // Logout user after successful deletion request
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
            
            return redirect()->route('account.reactivate')
                ->with('success', 'Account deletion requested. You have 30 days to reactivate your account.');
        }

        return back()->withErrors(['error' => 'Failed to request account deletion']);
    }

    /**
     * Show reactivate account page (Public - for users with deletion_requested status)
     */
    public function showReactivatePage(Request $request)
    {
        // If user is logged in and has deletion_requested status, show reactivate page
        // If not logged in, show login form with message
        $user = Auth::user();
        
        $deletionStatus = null;
        if ($user && $user->isDeletionRequested()) {
            $daysRemaining = null;
            if ($user->scheduled_deletion_at) {
                $daysRemaining = (int) now()->diffInDays($user->scheduled_deletion_at, false);
            }
            
            $deletionStatus = [
                'account_status' => $user->account_status,
                'deletion_requested_at' => $user->deletion_requested_at?->toISOString(),
                'scheduled_deletion_at' => $user->scheduled_deletion_at?->toISOString(),
                'days_remaining' => $daysRemaining,
                'can_reactivate' => $user->canReactivate(),
            ];
        }
        
        return Inertia::render('Account/Reactivate', [
            'deletionStatus' => $deletionStatus,
            'isLoggedIn' => Auth::check(),
        ]);
    }

    /**
     * Cancel deletion / Reactivate account (Web)
     */
    public function cancelDeletion(Request $request)
    {
        $user = Auth::user();

        if (!$user->isDeletionRequested()) {
            return back()->withErrors(['error' => 'No active deletion request found']);
        }

        // Simple update - reactivate account
        $user->account_status = 'active';
        $user->deletion_requested_at = null;
        $user->scheduled_deletion_at = null;
        $user->deletion_reason = null;
        $user->save();

        // Restore soft deleted data
        $this->deletionService->reactivateAccount($user, true);

        // Redirect to home after reactivation
        return redirect()->route('homepage')
            ->with('success', 'Account reactivated successfully. All your data has been restored.');
    }

    /**
     * Block a user (Web)
     */
    public function blockUser(Request $request, $userId)
    {
        $blocker = Auth::user();
        $blocked = User::findOrFail($userId);

        // Prevent self-blocking
        if ($blocker->id === $blocked->id) {
            return back()->withErrors(['error' => 'You cannot block yourself']);
        }

        // Check if already blocked
        if ($blocker->hasBlocked($blocked->id)) {
            return back()->with('success', 'User already blocked');
        }

        $success = $this->blockService->blockUser($blocker, $blocked);

        if ($success) {
            return back()->with('success', 'User blocked successfully');
        }

        return back()->withErrors(['error' => 'Failed to block user']);
    }

    /**
     * Unblock a user (Web)
     */
    public function unblockUser(Request $request, $userId)
    {
        $blocker = Auth::user();
        $blocked = User::findOrFail($userId);

        if (!$blocker->hasBlocked($blocked->id)) {
            return back()->withErrors(['error' => 'User is not blocked']);
        }

        $success = $this->blockService->unblockUser($blocker, $blocked);

        if ($success) {
            return back()->with('success', 'User unblocked successfully');
        }

        return back()->withErrors(['error' => 'Failed to unblock user']);
    }

    /**
     * Report a user (Web)
     */
    public function reportUser(Request $request, $userId)
    {
        $request->validate([
            'reason' => ['required', 'in:' . implode(',', Report::getReasons())],
            'description' => 'nullable|string|max:1000'
        ]);

        $reporter = Auth::user();
        $reportedUser = User::findOrFail($userId);

        $result = $this->reportService->reportUser(
            $reporter,
            $reportedUser,
            $request->reason,
            $request->description
        );

        if ($result['success']) {
            return back()->with('success', $result['message']);
        }

        return back()->withErrors(['error' => $result['message']]);
    }

    /**
     * Report a post (Web)
     */
    public function reportPost(Request $request, $postId)
    {
        $request->validate([
            'reason' => ['required', 'in:' . implode(',', Report::getReasons())],
            'description' => 'nullable|string|max:1000'
        ]);

        $reporter = Auth::user();
        $reportedPost = Post::findOrFail($postId);

        $result = $this->reportService->reportPost(
            $reporter,
            $reportedPost,
            $request->reason,
            $request->description
        );

        if ($result['success']) {
            return back()->with('success', $result['message']);
        }

        return back()->withErrors(['error' => $result['message']]);
    }

    /**
     * Show blocked users list (Web)
     */
    public function blockedUsers()
    {
        $user = Auth::user();
        $blockedUsers = User::whereHas('blockedByUsers', function($query) use ($user) {
            $query->where('blocker_id', $user->id);
        })->get();

        return Inertia::render('UserManagement/BlockedUsers', [
            'blockedUsers' => $blockedUsers
        ]);
    }

    /**
     * Show my reports (Web)
     */
    public function myReports()
    {
        $user = Auth::user();
        $reports = $this->reportService->getUserReports($user);

        return Inertia::render('UserManagement/MyReports', [
            'reports' => $reports
        ]);
    }

    /**
     * Show deletion status (Web)
     */
    public function deletionStatus()
    {
        $user = Auth::user();

        return Inertia::render('UserManagement/DeletionStatus', [
            'account_status' => $user->account_status,
            'deletion_requested_at' => $user->deletion_requested_at?->toISOString(),
            'scheduled_deletion_at' => $user->scheduled_deletion_at?->toISOString(),
            'days_remaining' => $user->scheduled_deletion_at ? now()->diffInDays($user->scheduled_deletion_at, false) : null,
            'can_reactivate' => $user->canReactivate(),
        ]);
    }
}

