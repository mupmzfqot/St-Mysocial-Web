<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;

class TeamController extends Controller
{
    public function get()
    {
        $query = User::query()
            ->whereHas('roles', function ($query) {
                $query->where('name', 'user');
            })
            ->whereNotNull('email_verified_at')
            ->where('is_active', true);

        if(auth()->user()) {
            $query->where('id', '!=', auth()->user()->id);
        }

        return $query->get();
    }
}
