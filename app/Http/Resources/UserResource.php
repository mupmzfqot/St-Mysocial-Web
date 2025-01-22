<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $roles = $this->roles->select('name', 'display_name')->toArray();
        return [
            'id'                => $this->id,
            'name'              => $this->name,
            'username'          => $this->username,
            'email'             => $this->email,
            'verified_account'  => (bool) $this->verified_account,
            'is_active'         => (bool) $this->is_active,
            'created_at'        => $this->created_at->toDateTimeString(),
            'updated_at'        => $this->updated_at->toDateTimeString(),
            'roles'             => $roles,
            'profile_img'       => $this->avatar,
            'cover_img'         => $this->cover_image,
            'user_type'         => in_array('user', array_column($roles, 'name')) ? 'st' : 'public',
            'last_login'        => null,
        ];
    }

}
