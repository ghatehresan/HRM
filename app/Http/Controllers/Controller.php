<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

abstract class Controller
{
    use AuthorizesRequests;

    /**
     * The authenticated user, typed. Every route using this runs behind
     * the 'auth' middleware, so the abort is unreachable in practice —
     * it exists to satisfy static analysis without nullable plumbing.
     */
    protected function authedUser(): User
    {
        $user = auth()->user();

        if (! $user instanceof User) {
            abort(403);
        }

        return $user;
    }
}
