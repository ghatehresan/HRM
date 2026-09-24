<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\ForgotPasswordRequest;
use App\Models\AuditLog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Password;
use Illuminate\View\View;

class PasswordResetLinkController extends Controller
{
    public function create(): View|RedirectResponse
    {
        if (auth()->check()) {
            return redirect()->route('home');
        }

        return view('auth.forgot-password');
    }

    public function store(ForgotPasswordRequest $request): RedirectResponse
    {
        $email = (string) $request->validated('email');

        $status = Password::sendResetLink(['email' => $email]);

        $event = match ($status) {
            Password::RESET_LINK_SENT => 'password_reset_requested',
            Password::RESET_THROTTLED => 'password_reset_throttled',
            default => 'password_reset_unknown',
        };

        AuditLog::record($event, null, null, ['email' => $email]);

        // Identical response either way: password-reset must not reveal
        // whether an email is registered (SECURITY.md §2).

        return back()->with('status', 'اگر حسابی با این ایمیل وجود داشته باشد، پیوند بازیابی ارسال شد.');
    }
}
