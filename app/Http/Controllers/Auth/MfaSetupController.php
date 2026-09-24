<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\MfaDisableRequest;
use App\Http\Requests\MfaSetupConfirmRequest;
use App\Services\AuthService;
use App\Services\MfaService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class MfaSetupController extends Controller
{
    public function show(MfaService $mfa): View
    {
        $user = $this->authedUser();
        $method = $user->primaryMfaMethod();

        if ($method !== null) {
            return view('mfa.manage', ['codesLeft' => count($method->recoveryCodeHashes())]);
        }

        $setup = $mfa->beginSetup($user);

        // The pending secret is encrypted at rest even inside the
        // server-side session (SESSION_ENCRYPT defaults to false).
        session()->put('mfa.setup_secret', encrypt($setup['secret']));

        return view('mfa.setup', ['qrSvg' => $setup['qr_svg'], 'secret' => $setup['secret']]);
    }

    public function confirm(MfaSetupConfirmRequest $request, MfaService $mfa, AuthService $auth): View|RedirectResponse
    {
        $user = $this->authedUser();
        $encrypted = session()->get('mfa.setup_secret');

        if (! is_string($encrypted)) {
            return redirect()->route('mfa.setup')->withErrors(['code' => 'نشست راه‌اندازی منقضی شد؛ دوباره تلاش کنید.']);
        }

        try {
            $secret = decrypt($encrypted);
        } catch (\Throwable) {
            $secret = null;
        }

        if (! is_string($secret)) {
            session()->forget('mfa.setup_secret');

            return redirect()->route('mfa.setup')->withErrors(['code' => 'نشست راه‌اندازی معتبر نیست؛ دوباره تلاش کنید.']);
        }

        $result = $mfa->confirmSetup($user, $secret, (string) $request->validated('code'));

        if ($result === null) {
            return back()->withErrors(['code' => 'کد واردشده درست نیست.']);
        }

        session()->forget('mfa.setup_secret');

        // Enrollment during a pending login completes the login.
        if (! session()->get('mfa_verified', false) && $user->requiresMfa()) {
            $auth->completeMfaChallenge($user);

            return view('mfa.codes', ['codes' => $result['codes'], 'next' => route('home')]);
        }

        return view('mfa.codes', ['codes' => $result['codes'], 'next' => route('mfa.setup')]);
    }

    public function regenerateCodes(MfaService $mfa): View|RedirectResponse
    {
        $user = $this->authedUser();
        $codes = $mfa->regenerateRecoveryCodes($user);

        if ($codes === null) {
            return redirect()->route('mfa.setup');
        }

        return view('mfa.codes', ['codes' => $codes, 'next' => route('mfa.setup')]);
    }

    public function destroy(MfaDisableRequest $request, MfaService $mfa): RedirectResponse
    {
        $user = $this->authedUser();

        if (! $mfa->disableWithPassword($user, (string) $request->validated('password'))) {
            return back()->withErrors(['password' => 'رمز عبور اشتباه است.']);
        }

        return redirect()->route('mfa.setup')->with('status', 'تأیید دومرحله‌ای غیرفعال شد.');
    }
}
