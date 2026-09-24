<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\User;
use App\Notifications\AccountLockedNotification;
use Illuminate\Support\Facades\Hash;

class AuthService
{
    /**
     * Verify credentials WITHOUT touching the session. Web and API share
     * this so lockout, active-checks and MFA requirements are identical.
     *
     * Failure responses intentionally merge "unknown email" and "wrong
     * password" (and inactive accounts) into INVALID so login cannot be
     * used to enumerate accounts. Only LOCKED is distinguishable — and a
     * locked account implies the attacker already knew a real email.
     */
    public function attempt(string $email, string $password): AuthResult
    {
        $user = User::where('email', strtolower(trim($email)))->first();

        if ($user === null) {
            // The attempted email is logged to expose credential-stuffing
            // patterns; it never affects the generic failure response.
            AuditLog::record('login_failed', null, null, ['email' => $email]);

            return new AuthResult(AuthResult::INVALID);
        }

        if ($user->isLockedOut()) {
            AuditLog::record('login_locked', $user, null, null, $user);

            return new AuthResult(AuthResult::LOCKED, $user);
        }

        if (! $user->is_active) {
            AuditLog::record('login_inactive', $user, null, null, $user);

            return new AuthResult(AuthResult::INACTIVE, $user);
        }

        if (! Hash::check($password, $user->password)) {
            $user->recordFailedLogin();

            if ($user->isLockedOut()) {
                $user->notify(new AccountLockedNotification($user->locked_until));
                AuditLog::record('login_locked', $user, null, null, $user);
            } else {
                AuditLog::record('login_failed', $user, null, null, $user);
            }

            return new AuthResult(AuthResult::INVALID, $user);
        }

        $user->clearLoginAttempts();

        if ($user->requiresMfa()) {
            return new AuthResult(AuthResult::MFA_REQUIRED, $user);
        }

        return new AuthResult(AuthResult::OK, $user);
    }

    /**
     * Establish the web session after attempt() succeeded. MFA users get
     * an UNVERIFIED session: RequireMfa middleware blocks every route
     * until completeMfaChallenge() runs (AUTHENTICATION.md §3).
     */
    public function completeWebLogin(User $user): void
    {
        auth()->guard('web')->login($user);
        session()->regenerate();
        session()->put('auth_at', now()->timestamp);
        session()->put('mfa_verified', ! $user->requiresMfa());

        if (! $user->requiresMfa()) {
            $this->finishLogin($user);
        }
    }

    /**
     * Mark MFA done: fresh session id, verified flag, login bookkeeping.
     */
    public function completeMfaChallenge(User $user): void
    {
        session()->regenerate();
        session()->put('mfa_verified', true);

        $this->finishLogin($user);
    }

    public function logout(): void
    {
        $user = auth()->guard('web')->user();

        if ($user instanceof User) {
            AuditLog::record('logout', $user, null, null, $user);
        }

        auth()->guard('web')->logout();
        session()->invalidate();
        session()->regenerateToken();
    }

    private function finishLogin(User $user): void
    {
        $user->forceFill(['last_login_at' => now()])->save();
        AuditLog::record('login', $user, null, null, $user);
    }
}
