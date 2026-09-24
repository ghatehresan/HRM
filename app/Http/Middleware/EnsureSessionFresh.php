<?php

namespace App\Http\Middleware;

use App\Models\AuditLog;
use App\Models\User;
use App\Services\AuthService;
use Closure;
use Illuminate\Http\Request;

class EnsureSessionFresh
{
    /**
     * Absolute session expiry (SECURITY.md §2): independent of activity,
     * measured from login. API tokens are stateless and carry their own
     * expires_at instead — this middleware skips requests without a
     * session clock, so it is safe to share across stacks.
     */
    public function handle(Request $request, Closure $next): mixed
    {
        $user = $request->user();

        if ($user instanceof User && session()->has('auth_at')) {
            $hours = (int) config('hrm.auth.absolute_timeout_hours', 12);
            $authAt = (int) session()->get('auth_at', 0);

            if ($authAt > 0 && now()->timestamp - $authAt > $hours * 3600) {
                app(AuthService::class)->logout();
                AuditLog::record('session_expired', $user, null, null, $user);

                if ($request->expectsJson()) {
                    return response()->json(['message' => 'نشست منقضی شده است.'], 401);
                }

                return redirect()->route('login')->withErrors(['email' => 'نشست شما منقضی شد؛ لطفاً دوباره وارد شوید.']);
            }
        }

        return $next($request);
    }
}
