<?php

namespace App\Models;

use Database\Factories\MfaMethodFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;

/**
 * One MFA credential. M2 supports `totp` only (DATABASE.md §3.5); the
 * `type` column reserves space for future methods (webauthn, sms).
 * Secrets are encrypted at rest; recovery codes are stored as an
 * encrypted array of bcrypt hashes (single-use, consumed on verify).
 *
 * @property int $id
 * @property int $user_id
 * @property string $type
 * @property string $secret
 * @property string[]|null $recovery_codes
 * @property bool $is_primary
 * @property Carbon|null $last_used_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read User $user
 */
#[Fillable(['user_id', 'type', 'secret', 'recovery_codes', 'is_primary', 'last_used_at'])]
#[Hidden(['secret', 'recovery_codes'])]
class MfaMethod extends Model
{
    /** @use HasFactory<MfaMethodFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'secret' => 'encrypted',
            'recovery_codes' => 'encrypted:array',
            'is_primary' => 'boolean',
            'last_used_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return string[]
     */
    public function recoveryCodeHashes(): array
    {
        $codes = $this->recovery_codes;

        if (! is_array($codes)) {
            return [];
        }

        $hashes = [];

        foreach ($codes as $code) {
            if (is_string($code)) {
                $hashes[] = $code;
            }
        }

        return $hashes;
    }

    /**
     * Verify a recovery code and burn it so it can never be reused.
     */
    public function consumeRecoveryCode(string $code): bool
    {
        foreach ($this->recoveryCodeHashes() as $index => $hash) {
            if (Hash::check($code, $hash)) {
                $hashes = $this->recoveryCodeHashes();
                unset($hashes[$index]);
                $this->forceFill(['recovery_codes' => array_values($hashes)])->save();
                $this->touchLastUsed();

                return true;
            }
        }

        return false;
    }

    public function touchLastUsed(): void
    {
        $this->forceFill(['last_used_at' => now()])->save();
    }
}
