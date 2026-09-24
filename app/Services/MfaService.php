<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\MfaMethod;
use App\Models\User;
use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use OTPHP\TOTP;

class MfaService
{
    /**
     * Start enrollment. NOTHING is stored yet: the caller keeps `secret`
     * in the session and only confirmSetup() persists (after a valid
     * code), so a dropped flow can never half-enroll a broken secret.
     *
     * @return array{secret: string, qr_svg: string}
     */
    public function beginSetup(User $user): array
    {
        $totp = TOTP::generate();
        $totp->setLabel($user->email);
        $totp->setIssuer((string) config('hrm.company_name', 'HRM'));

        return [
            'secret' => $totp->getSecret(),
            'qr_svg' => $this->qrSvg($totp->getProvisioningUri()),
        ];
    }

    /**
     * Verify the first code and persist the method with fresh recovery
     * codes. Returns the PLAINTEXT codes exactly once — the caller must
     * display them and never store them.
     *
     * @return array{method: MfaMethod, codes: string[]}|null
     */
    public function confirmSetup(User $user, string $secret, string $code): ?array
    {
        if (! $this->verifyTotp($secret, $code)) {
            AuditLog::record('mfa_setup_failed', $user, null, null, $user);

            return null;
        }

        $codes = $this->generateRecoveryCodes();
        $hashes = [];

        foreach ($codes as $plain) {
            $hashes[] = Hash::make($this->normalizeRecoveryCode($plain));
        }

        $method = $user->mfaMethods()->updateOrCreate(
            ['type' => 'totp'],
            [
                'secret' => $secret,
                'recovery_codes' => $hashes,
                'is_primary' => true,
                'last_used_at' => now(),
            ],
        );

        AuditLog::record('mfa_enrolled', $user, null, null, $user);

        return ['method' => $method, 'codes' => $codes];
    }

    /**
     * Challenge verification: TOTP first (fast path), then single-use
     * recovery codes. Exactly one audit row per attempt.
     */
    public function verifyChallenge(User $user, string $code): bool
    {
        $method = $user->primaryMfaMethod();

        if ($method === null) {
            return false;
        }

        if ($this->verifyTotp($method->secret, $code)) {
            $method->touchLastUsed();
            AuditLog::record('mfa_verified', $user, null, null, $user);

            return true;
        }

        if ($method->consumeRecoveryCode($this->normalizeRecoveryCode($code))) {
            AuditLog::record('mfa_recovery_used', $user, null, null, $user);

            return true;
        }

        AuditLog::record('mfa_failed', $user, null, null, $user);

        return false;
    }

    public function verifyTotp(string $secret, string $code): bool
    {
        $code = preg_replace('/\D/', '', $code) ?? '';

        if ($code === '') {
            return false;
        }

        try {
            $totp = TOTP::createFromSecret($secret);
        } catch (\Throwable) {
            return false;
        }

        $leeway = (int) config('hrm.auth.mfa_totp_leeway', 29);

        try {
            return $totp->verify($code, null, $leeway);
        } catch (\Throwable) {
            // Fail closed on misconfiguration (e.g. leeway >= period).
            return false;
        }
    }

    /**
     * @return string[] plaintext codes (display once, never store)
     */
    public function regenerateRecoveryCodes(User $user): ?array
    {
        $method = $user->primaryMfaMethod();

        if ($method === null) {
            return null;
        }

        $codes = $this->generateRecoveryCodes();
        $hashes = [];

        foreach ($codes as $plain) {
            $hashes[] = Hash::make($this->normalizeRecoveryCode($plain));
        }

        $method->forceFill(['recovery_codes' => $hashes])->save();
        AuditLog::record('mfa_codes_regenerated', $user, null, null, $user);

        return $codes;
    }

    /**
     * Disable MFA only with the current password (prevents a brief
     * unattended session from silently stripping the second factor).
     */
    public function disableWithPassword(User $user, string $password): bool
    {
        if (! Hash::check($password, $user->password)) {
            return false;
        }

        $user->mfaMethods()->delete();
        AuditLog::record('mfa_disabled', $user, null, null, $user);

        return true;
    }

    /**
     * @return string[] display-form codes (XXXX-XXXX); hashed normalized.
     */
    public function generateRecoveryCodes(): array
    {
        $count = max(1, (int) config('hrm.auth.mfa_recovery_codes', 8));
        $codes = [];

        for ($i = 0; $i < $count; $i++) {
            $codes[] = Str::upper(Str::random(4)).'-'.Str::upper(Str::random(4));
        }

        return $codes;
    }

    public function normalizeRecoveryCode(string $code): string
    {
        return Str::upper((string) preg_replace('/[^A-Za-z0-9]/', '', $code));
    }

    /**
     * Pure-PHP SVG markup (no gd/imagick), inlined as an <svg> element —
     * no data: URIs, so the strict CSP stays intact.
     */
    public function qrSvg(string $content): string
    {
        $options = new QROptions(['outputType' => QRCode::OUTPUT_MARKUP_SVG, 'scale' => 6, 'margin' => 1]);

        return (string) (new QRCode($options))->render($content);
    }
}
