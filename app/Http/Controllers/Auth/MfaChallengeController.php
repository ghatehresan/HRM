<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\MfaChallengeRequest;
use App\Services\AuthService;
use App\Services\MfaService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class MfaChallengeController extends Controller
{
    public function show(): View|RedirectResponse
    {
        $user = $this->authedUser();

        if (! $user->requiresMfa() || session()->get('mfa_verified', false)) {
            return redirect()->route('home');
        }

        if ($user->primaryMfaMethod() === null) {
            return redirect()->route('mfa.setup');
        }

        return view('mfa.challenge');
    }

    public function store(MfaChallengeRequest $request, MfaService $mfa, AuthService $auth): RedirectResponse
    {
        $user = $this->authedUser();

        if ($mfa->verifyChallenge($user, (string) $request->validated('code'))) {
            session()->forget('mfa.attempts');
            $auth->completeMfaChallenge($user);

            return redirect()->intended(route('home'));
        }

        $attempts = (int) session()->get('mfa.attempts', 0) + 1;
        session()->put('mfa.attempts', $attempts);

        $max = (int) config('hrm.auth.mfa_max_attempts', 5);

        if ($attempts >= $max) {
            $auth->logout();

            return redirect()->route('login')->withErrors(['email' => 'به دلیل تلاش‌های ناموفق، نشست بسته شد؛ دوباره وارد شوید.']);
        }

        return back()->withErrors(['code' => 'کد واردشده درست نیست.']);
    }
}
