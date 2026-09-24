<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Rules\StrongPassword;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

final class StrongPasswordTest extends TestCase
{
    public function test_short_passwords_fail(): void
    {
        $this->assertRuleFails('Ab1!x');
    }

    public function test_denylisted_passwords_fail(): void
    {
        $this->assertRuleFails('password1234');
    }

    public function test_password_containing_email_local_part_fails(): void
    {
        $this->assertRuleFails('Niloufar-1405-Xy!', 'niloufar@example.com');
    }

    public function test_guessable_passwords_fail_zxcvbn(): void
    {
        $this->assertRuleFails('passwordpassword');
    }

    public function test_strong_passwords_pass(): void
    {
        $validator = Validator::make(
            ['password' => 'X7#mQ9!vL2$pR5&kD8@nF4'],
            ['password' => [new StrongPassword('someone@example.com')]]
        );

        $this->assertTrue($validator->passes(), 'Expected the strong fixture to pass.');
    }

    private function assertRuleFails(string $candidate, ?string $email = null): void
    {
        $validator = Validator::make(
            ['password' => $candidate],
            ['password' => [new StrongPassword($email ?? 'someone@example.com')]]
        );

        $this->assertTrue($validator->fails(), "Expected [{$candidate}] to fail.");
    }
}
