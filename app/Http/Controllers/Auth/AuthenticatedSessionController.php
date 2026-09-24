<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use App\Services\AuthResult;
use App\Services\AuthService;
use App\Support\PersianNumbers;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    public function create(): View|RedirectResponse
    {
        if (auth()->check()) {
            return redirect()->route('home');
        }

        return view('auth.login');
    }

    public function store(LoginRequest $request, AuthService $auth): RedirectResponse
    {
        $result = $auth->attempt(
            (string) $request->validated('email'),
            (string) $request->validated('password'),
        );

        if ($result->status === AuthResult::LOCKED) {
            $minutes = 1;

            if ($result->user?->locked_until !== null) {
                $minutes = max(1, (int) $result->user->locked_until->diffInMinutes(now()));
            }

            return back()->withErrors([
                'email' => 'حساب شما به دلیل تلاش‌های ناموفق قفل شده است. حدود'.PersianNumbers::toFa((string) $minutes).' دقیقهٔ دیگر تلاش کنید.',
            ])->onlyInput('email');
        }

        if (! $result->succeeded() && ! $result->needsMfa()) {
            return back()->withErrors([
                'email' => 'مشخصات ورود اشتباه است.',
            ])->onlyInput('email');
        }

        $user = $result->user;

        if ($user === null) {
            return back()->withErrors([
                'email' => 'مشخصات ورود اشتباه است.',
            ])->onlyInput('email');
        }

        $auth->completeWebLogin($user);

        if ($result->needsMfa()) {
            return $user->primaryMfaMethod() === null
                ? redirect()->route('mfa.setup')
                : redirect()->route('mfa.challenge');
        }

        return redirect()->intended(route('home'));
    }

    public function destroy(AuthService $auth): RedirectResponse
    {
        $auth->logout();

        return redirect()->route('login');
    }
}
