<?php

namespace App\Actions\Auth;

use App\Models\User;

class RegisterUserResult
{
    public function __construct(
        public readonly User $user,
        public readonly string $message,
    ) {}
}
