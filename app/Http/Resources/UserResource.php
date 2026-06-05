<?php

namespace App\Http\Resources;

use App\Models\UserBlock;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    /**
     * Memoized block sets for the authenticated user, keyed by auth user id.
     * Avoids running 2 queries per serialized user (N+1) when rendering a
     * collection — the sets are fetched once per request and reused.
     *
     * @var array<int, array{blocked: int[], blockedBy: int[]}>
     */
    protected static array $blockedSetsCache = [];

    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $roles = $this->roles->select('name', 'display_name')->toArray();
        $authUser = $request->user();
        $blockedSets = static::blockedSetsFor($authUser);

        return [
            'id' => $this->id,
            'name' => $this->name,
            'username' => $this->username,
            'email' => $this->email,
            'verified_account' => (bool) $this->verified_account,
            'is_active' => (bool) $this->is_active,
            'created_at' => $this->created_at->toDateTimeString(),
            'updated_at' => $this->updated_at->toDateTimeString(),
            'roles' => $roles,
            'gender' => $this->gender,
            'profile_img' => $this->avatar,
            'cover_img' => $this->cover_image,
            'user_type' => in_array('user', array_column($roles, 'name')) ? 'st' : 'public',
            'last_login' => $this->last_login?->toDateTimeString(),

            // Account lifecycle status
            'account_status' => $this->account_status,
            'is_deletion_requested' => $this->isDeletionRequested(),

            // Block status (relative to the currently authenticated user)
            // is_blocked     => this user has been blocked BY me (auth user)
            // has_blocked_me => this user has blocked ME (auth user)
            'is_blocked' => in_array((int) $this->id, $blockedSets['blocked'], true),
            'has_blocked_me' => in_array((int) $this->id, $blockedSets['blockedBy'], true),

            // How many users this user has blocked (uses withCount when eager-loaded)
            'blocked_users_count' => $this->whenCounted('blockedUsers', $this->blocked_users_count ?? 0),
        ];
    }

    /**
     * Resolve (and memoize) the authenticated user's block sets.
     *
     * Returns two int lists:
     *  - blocked:   ids the auth user has blocked  (blocker_id = auth)
     *  - blockedBy: ids that have blocked the auth user (blocked_id = auth)
     *
     * @return array{blocked: int[], blockedBy: int[]}
     */
    protected static function blockedSetsFor(?Authenticatable $authUser): array
    {
        if (! $authUser) {
            return ['blocked' => [], 'blockedBy' => []];
        }

        $authId = (int) $authUser->getAuthIdentifier();

        if (! isset(static::$blockedSetsCache[$authId])) {
            $blocks = UserBlock::query()
                ->where('blocker_id', $authId)
                ->orWhere('blocked_id', $authId)
                ->get(['blocker_id', 'blocked_id']);

            static::$blockedSetsCache[$authId] = [
                'blocked' => $blocks->where('blocker_id', $authId)
                    ->pluck('blocked_id')->map(fn ($id) => (int) $id)->all(),
                'blockedBy' => $blocks->where('blocked_id', $authId)
                    ->pluck('blocker_id')->map(fn ($id) => (int) $id)->all(),
            ];
        }

        return static::$blockedSetsCache[$authId];
    }
}
