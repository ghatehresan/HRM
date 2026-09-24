<?php

declare(strict_types=1);

namespace Tests\Feature\Security;

use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

final class HashingTest extends TestCase
{
    public function test_default_driver_hashes_and_verifies(): void
    {
        $hash = Hash::make('some-password');

        $this->assertTrue(Hash::check('some-password', $hash));
        $this->assertFalse(Hash::check('other-password', $hash));
    }

    public function test_argon2id_works_when_available(): void
    {
        if (! defined('PASSWORD_ARGON2ID')) {
            $this->markTestSkipped('libsodium is not available.');
        }

        $hash = Hash::driver('argon2id')->make('some-password');

        $this->assertStringStartsWith('$argon2id$', $hash);
        $this->assertTrue(Hash::driver('argon2id')->check('some-password', $hash));
    }

    public function test_testing_env_uses_fast_bcrypt(): void
    {
        $this->assertSame('bcrypt', config('hashing.driver'));
    }
}
