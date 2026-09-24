<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Models\MfaMethod;
use App\Models\User;
use App\Services\MfaService;
use App\Support\PersianNumbers;
use OTPHP\TOTP;
use Tests\TestCase;

final class MfaTest extends TestCase
{
    public function test_privileged_user_is_redirected_to_setup_when_unenrolled(): void
    {
        $user = $this->userWithRoles(['hr-admin']);

        $this->post(route('login.store'), ['email' => $user->email, 'password' => 'password'])
            ->assertRedirect(route('mfa.setup'));

        $this->assertAuthenticatedAs($user);

        // Protected pages stay out of reach until enrollment completes.
        $this->get(route('password.change.edit'))->assertRedirect(route('mfa.setup'));
        $this->get(route('admin.users.index'))->assertRedirect(route('mfa.setup'));
    }

    public function test_user_can_enroll_totp_and_see_recovery_codes_once(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $show = $this->get(route('mfa.setup'))->assertOk()->assertSee('<svg', false);
        $secret = $show->viewData('secret');
        $this->assertIsString($secret);

        $code = TOTP::createFromSecret($secret)->now();

        $confirm = $this->post(route('mfa.setup.confirm'), ['code' => $code])->assertOk();
        $confirm->assertViewIs('mfa.codes');

        $codes = $confirm->viewData('codes');
        $this->assertIsArray($codes);
        $this->assertCount(8, $codes);

        $this->assertDatabaseHas('mfa_methods', ['user_id' => $user->id, 'type' => 'totp']);
        $this->assertDatabaseHas('audit_logs', ['event' => 'mfa_enrolled']);
    }

    public function test_enrollment_rejects_wrong_code(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $this->get(route('mfa.setup'))->assertOk();

        $this->post(route('mfa.setup.confirm'), ['code' => '123456'])
            ->assertSessionHasErrors('code');

        $this->assertSame(0, MfaMethod::where('user_id', $user->id)->count());
        $this->assertDatabaseHas('audit_logs', ['event' => 'mfa_setup_failed']);
    }

    public function test_enrolled_user_must_pass_challenge_after_password_login(): void
    {
        $user = User::factory()->create();
        $secret = $this->enrollWithKnownSecret($user);

        $this->post(route('login.store'), ['email' => $user->email, 'password' => 'password'])
            ->assertRedirect(route('mfa.challenge'));

        $this->get(route('password.change.edit'))->assertRedirect(route('mfa.challenge'));

        $code = TOTP::createFromSecret($secret)->now();

        $this->post(route('mfa.challenge.store'), ['code' => $code])->assertRedirect(route('home'));

        $this->get(route('password.change.edit'))->assertOk();
        $this->assertDatabaseHas('audit_logs', ['event' => 'mfa_verified']);
        $this->assertDatabaseHas('audit_logs', ['event' => 'login']);
    }

    public function test_challenge_accepts_persian_digits(): void
    {
        $user = User::factory()->create();
        $secret = $this->enrollWithKnownSecret($user);

        $this->post(route('login.store'), ['email' => $user->email, 'password' => 'password'])
            ->assertRedirect(route('mfa.challenge'));

        $code = TOTP::createFromSecret($secret)->now();

        $this->post(route('mfa.challenge.store'), ['code' => PersianNumbers::toFa($code)])
            ->assertRedirect(route('home'));
    }

    public function test_challenge_rejects_wrong_code(): void
    {
        $user = User::factory()->create();
        $secret = $this->enrollWithKnownSecret($user);

        $this->post(route('login.store'), ['email' => $user->email, 'password' => 'password'])
            ->assertRedirect(route('mfa.challenge'));

        $actual = TOTP::createFromSecret($secret)->now();
        $wrong = $actual === '000000' ? '000001' : '000000';

        $this->post(route('mfa.challenge.store'), ['code' => $wrong])
            ->assertSessionHasErrors('code');

        $this->assertDatabaseHas('audit_logs', ['event' => 'mfa_failed']);
        $this->get(route('password.change.edit'))->assertRedirect(route('mfa.challenge'));
    }

    public function test_recovery_code_works_once(): void
    {
        $user = User::factory()->create();
        $secret = TOTP::generate()->getSecret();

        $result = app(MfaService::class)->confirmSetup($user, $secret, TOTP::createFromSecret($secret)->now());
        $this->assertIsArray($result);

        $codes = $result['codes'];
        $this->assertNotEmpty($codes);
        $code = $codes[0];
        $this->assertIsString($code);

        $this->post(route('login.store'), ['email' => $user->email, 'password' => 'password'])
            ->assertRedirect(route('mfa.challenge'));

        $this->post(route('mfa.challenge.store'), ['code' => $code])->assertRedirect(route('home'));
        $this->assertDatabaseHas('audit_logs', ['event' => 'mfa_recovery_used']);

        // The same code is burned: a fresh login cannot reuse it.
        $this->post(route('logout'))->assertRedirect(route('login'));

        $this->post(route('login.store'), ['email' => $user->email, 'password' => 'password'])
            ->assertRedirect(route('mfa.challenge'));

        $this->post(route('mfa.challenge.store'), ['code' => $code])
            ->assertSessionHasErrors('code');
    }

    public function test_five_mfa_failures_drop_the_session(): void
    {
        $user = User::factory()->create();
        $this->enrollWithKnownSecret($user);

        $this->post(route('login.store'), ['email' => $user->email, 'password' => 'password'])
            ->assertRedirect(route('mfa.challenge'));

        for ($i = 0; $i < 4; $i++) {
            $this->post(route('mfa.challenge.store'), ['code' => '000000'])
                ->assertSessionHasErrors('code');
        }

        $this->post(route('mfa.challenge.store'), ['code' => '000000'])
            ->assertRedirect(route('login'));

        $this->assertGuest();
    }

    public function test_optional_user_without_mfa_skips_challenge(): void
    {
        $user = User::factory()->create();

        $this->post(route('login.store'), ['email' => $user->email, 'password' => 'password'])
            ->assertRedirect(route('home'));

        $this->get(route('password.change.edit'))->assertOk();
    }

    public function test_mfa_can_be_managed_and_disabled_with_password(): void
    {
        $user = User::factory()->create();
        $this->enrollWithKnownSecret($user);
        $this->actingAs($user);

        $this->get(route('mfa.setup'))->assertOk()->assertSee('کدهای بازیابی جدید');

        $this->post(route('mfa.codes.regenerate'))->assertOk()->assertViewIs('mfa.codes');

        $this->delete(route('mfa.destroy'), ['password' => 'wrong'])
            ->assertSessionHasErrors('password');
        $this->assertSame(1, MfaMethod::where('user_id', $user->id)->count());

        $this->delete(route('mfa.destroy'), ['password' => 'password'])
            ->assertRedirect(route('mfa.setup'));
        $this->assertSame(0, MfaMethod::where('user_id', $user->id)->count());
        $this->assertDatabaseHas('audit_logs', ['event' => 'mfa_disabled']);
    }

    private function enrollWithKnownSecret(User $user): string
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
