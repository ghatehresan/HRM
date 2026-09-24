<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;

class CheckPermission
{
    public function handle(Request $request, Closure $next, string $permission): mixed
    {
        $user = $request->user();

        if (! $user instanceof User || ! $user->hasPermission($permission)) {
            abort(403);
        }

        return $next($request);
    }
}
