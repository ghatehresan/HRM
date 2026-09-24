<?php

namespace App\Rules;

use App\Support\PersianNumbers;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use ZxcvbnPhp\Zxcvbn;

/**
 * Password policy (SECURITY.md §2): minimum length + local denylist +
 * email-part rejection + zxcvbn strength score. Fully offline — unlike
 * breach-corpus checks it needs no network (XAMPP-safe).
 */
class StrongPassword implements ValidationRule
{
    /**
     * Lowercase exact-match denylist. Substring/common-pattern cases are
     * caught by zxcvbn scoring below; this list only needs the infamous
     * exact passwords (all padded here to ≥12 chars where realistic).
     *
     * @var string[]
     */
    private const DENYLIST = [
        'password',
        'password1',
        'password12',
        'password123',
        'password1234',
        '123456789012',
        'qwerty123456',
        'letmein12345',
        'welcome12345',
        'admin1234567',
        'iloveyou1234',
        'ghatehresan12',
    ];

    public function __construct(private readonly ?string $email = null) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || $value === '') {
            return; // 'required'/'string' rules own this case.
        }

        $min = (int) config('hrm.auth.password_min_length', 12);

        if (mb_strlen($value) < $min) {
            $fail('رمز عبور باید حداقل '.PersianNumbers::toFa((string) $min).' کاراکتر باشد.');

            return;
        }

        $lower = mb_strtolower($value);

        if (in_array($lower, self::DENYLIST, true)) {
            $fail('این رمز عبور خیلی رایج است؛ یک رمز متفاوت انتخاب کنید.');

            return;
        }

        if ($this->email !== null) {
            $local = strtolower((string) strstr($this->email, '@', true));

            if (strlen($local) >= 4 && str_contains($lower, $local)) {
                $fail('رمز عبور نباید شامل بخش اول ایمیل شما باشد.');

                return;
            }
        }

        $score = (int) (new Zxcvbn)->passwordStrength($value)['score'];
        $minScore = (int) config('hrm.auth.password_zxcvbn_min_score', 3);

        if ($score < $minScore) {
            $fail('این رمز به‌راحتی حدس زده می‌شود؛ طولانی‌تر و غیرقابل‌پیش‌بینی‌تر انتخاب کنید.');
        }
    }
}
