<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\PasswordUpdateRequest;
use App\Models\AuditLog;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class PasswordController extends Controller
{
    public function edit(): View
    {
        return view('profile.password');
    }

    public function update(PasswordUpdateRequest $request): RedirectResponse
    {
        $user = $this->authedUser();

        $user->forceFill([
            'password' => (string) $request->validated('password'),
            'password_changed_at' => now(),
        ])->save();

        $user->tokens()->delete();

        AuditLog::record('password_changed', $user, null, null, $user);

        return back()->with('status', 'رمز عبور با موفقیت تغییر کرد.');
    }
}
