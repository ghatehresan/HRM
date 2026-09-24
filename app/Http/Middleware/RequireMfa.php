<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;

class RequireMfa
{
    /**
     * Second gate after 'auth': MFA-bound users with an unverified
     * session can only reach the challenge/setup routes (which carry
     * 'auth' but NOT this middleware). API tokens prove MFA at
     * issuance time instead, so this middleware never applies there.
     */
    public function handle(Request $request, Closure $next): mixed
    {
        $user = $request->user();

        if (! $user instanceof User) {
            return $next($request);
        }

        if (! $user->requiresMfa() || session()->get('mfa_verified', false)) {
            return $next($request);
        }

        if ($request->expectsJson()) {
            abort(response()->json(['message' => 'MFA verification required.'], 403));
        }

        return $user->primaryMfaMethod() === null
            ? redirect()->route('mfa.setup')
            : redirect()->route('mfa.challenge');
    }
}
