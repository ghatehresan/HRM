<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\ResetPasswordRequest;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class NewPasswordController extends Controller
{
    public function create(string $token): View|RedirectResponse
    {
        if (auth()->check()) {
            return redirect()->route('home');
        }

        return view('auth.reset-password', ['token' => $token]);
    }

    public function store(ResetPasswordRequest $request): RedirectResponse
    {
        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (User $user, string $password): void {
                $user->forceFill([
                    'password' => $password,
                    'password_changed_at' => now(),
                    'remember_token' => Str::random(60),
                ])->save();

                // A fresh password kills every API token and clears any
                // lockout: whoever holds the reset link owns the account.
                $user->tokens()->delete();
                $user->clearLoginAttempts();

                AuditLog::record('password_reset', $user, null, null, $user);
            }
        );

        if ($status !== Password::PASSWORD_RESET) {
            throw ValidationException::withMessages(['email' => 'پیوند بازیابی معتبر نیست یا منقضی شده است.']);
        }

        return redirect()->route('login')->with('status', 'رمز عبور با موفقیت تغییر کرد؛ وارد شوید.');
    }
}
