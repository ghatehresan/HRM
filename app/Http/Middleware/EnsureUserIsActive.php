<?php

namespace App\Http\Middleware;

use App\Models\AuditLog;
use App\Models\User;
use App\Services\AuthService;
use Closure;
use Illuminate\Http\Request;

class EnsureUserIsActive
{
    public function handle(Request $request, Closure $next): mixed
    {
        $user = $request->user();

        if ($user instanceof User && ! $user->is_active) {
            app(AuthService::class)->logout();
            AuditLog::record('session_revoked', $user, null, null, $user);

            if ($request->expectsJson()) {
                return response()->json(['message' => 'حساب شما غیرفعال شده است.'], 401);
            }

            return redirect()->route('login')->withErrors(['email' => 'حساب شما غیرفعال شده است.']);
        }

        return $next($request);
    }
}
