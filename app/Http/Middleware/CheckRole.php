<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;

class CheckRole
{
    public function handle(Request $request, Closure $next, string ...$slugs): mixed
    {
        $user = $request->user();

        if (! $user instanceof User || ! $user->hasRole(...$slugs)) {
            abort(403);
        }

        return $next($request);
    }
}
