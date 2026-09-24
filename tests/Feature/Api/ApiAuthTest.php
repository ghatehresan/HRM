<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Models\MfaMethod;
use App\Models\User;
use OTPHP\TOTP;
use Tests\TestCase;

final class ApiAuthTest extends TestCase
{
    public function test_token_can_be_issued_and_used(): void
    {
        $user = User::factory()->create();

        $issue = $this->postJson('/api/v1/tokens', [
            'email' => $user->email,
            'password' => 'password',
            'name' => 'e2e',
        ])->assertCreated()->assertJsonStructure(['token', 'expires_at']);

        $token = $issue->json('token');
        $this->assertIsString($token);

        $this->getJson('/api/v1/me', ['Authorization' => "Bearer {$token}"])
            ->assertOk()
            ->assertJson(['email' => $user->email]);
    }

    public function test_me_without_token_is_401_json(): void
    {
        $this->getJson('/api/v1/me')->assertUnauthorized();
        $this->get('/api/v1/me')->assertUnauthorized();
    }

    public function test_token_issuance_requires_mfa_code_for_enrolled_accounts(): void
    {
        $user = User::factory()->create();
        $secret = $this->enroll($user);

        $this->postJson('/api/v1/tokens', ['email' => $user->email, 'password' => 'password'])
            ->assertStatus(422)
            ->assertJsonValidationErrors('mfa_code');

        $code = TOTP::createFromSecret($secret)->now();

        $this->postJson('/api/v1/tokens', [
            'email' => $user->email,
            'password' => 'password',
            'mfa_code' => $code,
        ])->assertCreated();
    }

    public function test_tokens_can_be_listed_and_revoked(): void
    {
        $user = User::factory()->create();
        $mine = $user->createToken('mine');
        $other = User::factory()->create()->createToken('theirs');

        $headers = ['Authorization' => 'Bearer '.$mine->plainTextToken];

        $this->getJson('/api/v1/tokens', $headers)->assertOk()->assertJsonCount(1, 'data');

        // Somebody else's token cannot be revoked through this account.
        $this->deleteJson("/api/v1/tokens/{$other->accessToken->id}", [], $headers)->assertNotFound();

        $this->deleteJson("/api/v1/tokens/{$mine->accessToken->id}", [], $headers)->assertNoContent();

        $this->getJson('/api/v1/me', $headers)->assertUnauthorized();
    }

    public function test_expired_token_is_rejected(): void
    {
        $user = User::factory()->create();
        $issued = $user->createToken('expired', ['*'], now()->subMinute());

        $this->getJson('/api/v1/me', ['Authorization' => 'Bearer '.$issued->plainTextToken])
            ->assertUnauthorized();
    }

    public function test_inactive_user_token_is_rejected(): void
    {
        $user = User::factory()->create();
        $issued = $user->createToken('device');

        $user->forceFill(['is_active' => false])->save();

        $this->getJson('/api/v1/me', ['Authorization' => 'Bearer '.$issued->plainTextToken])
            ->assertUnauthorized();
    }

    private function enroll(User $user): string
    {
        $secret = TOTP::generate()->getSecret();

        MfaMethod::create([
            'user_id' => $user->id,
            'type' => 'totp',
            'secret' => $secret,
            'recovery_codes' => [],
            'is_primary' => true,
        ]);

        return $secret;
    }
}
