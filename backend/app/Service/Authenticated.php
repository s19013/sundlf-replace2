<?php

namespace App\Service;

use App\Exceptions\UnauthenticatedException;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class Authenticated
{
    public function user(): User
    {
        /** @var User|null $user */
        $user = Auth::user();

        if ($user === null) {
            throw new UnauthenticatedException;
        }

        return $user;
    }
}
