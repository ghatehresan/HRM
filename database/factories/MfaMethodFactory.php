<?php

namespace Database\Factories;

use App\Models\MfaMethod;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<MfaMethod>
 */
class MfaMethodFactory extends Factory
{
    /**
     * NOTE: the factory secret is a random placeholder, NOT a working
     * TOTP secret. Tests that verify real codes must create the method
     * through MfaService (which generates a valid otphp secret).
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'type' => 'totp',
            'secret' => Str::random(32),
            'recovery_codes' => [],
            'is_primary' => true,
        ];
    }
}
