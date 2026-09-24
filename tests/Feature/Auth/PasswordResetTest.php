<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Models\User;
use App\Notifications\ResetPasswordNotification;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

final class PasswordResetTest extends TestCase
{
    private const STRONG = 'X7#mQ9!vL2$pR5&kD8@nF4';

    public function test_reset_link_can_be_requested(): void
    {
        Notification::fake();

        $user = User::factory()->create();

        $this->post(route('password.email'), ['email' => $user->email])
            ->assertSessionHasNoErrors()
            ->assertSessionHas('status');

        Notification::assertSentTo($user, ResetPasswordNotification::class);
        $this->assertDatabaseHas('audit_logs', ['event' => 'password_reset_requested']);
    }

    public function test_reset_link_request_is_identical_for_unknown_email(): void
    {
        Notification::fake();

        $this->post(route('password.email'), ['email' => 'ghost@example.com'])
            ->assertSessionHasNoErrors()
            ->assertSessionHas('status');

        Notification::assertNothingSent();
        $this->assertDatabaseHas('audit_logs', ['event' => 'password_reset_unknown']);
    }

    public function test_password_can_be_reset_with_valid_token(): void
    {
        $user = User::factory()->create();
        $token = Password::broker()->createToken($user);

        $this->get(route('password.reset', ['token' => $token, 'email' => $user->email]))->assertOk();

        $this->post(route('password.update'), [
            'token' => $token,
            'email' => $user->email,
            'password' => self::STRONG,
            'password_confirmation' => self::STRONG,
        ])->assertRedirect(route('login'));

        $this->assertTrue(Hash::check(self::STRONG, $user->refresh()->password));
        $this->assertNotNull($user->password_changed_at);
        $this->assertDatabaseHas('audit_logs', ['event' => 'password_reset']);

        // The new password works for login.
        $this->post(route('login.store'), ['email' => $user->email, 'password' => self::STRONG])
            ->assertRedirect(route('home'));
    }

    public function test_reset_revokes_api_tokens_and_clears_lockout(): void
    {
        $user = User::factory()->create();
        $user->createToken('old-device');
        $user->recordFailedLogin();

        $token = Password::broker()->createToken($user);

        $this->post(route('password.update'), [
            'token' => $token,
            'email' => $user->email,
            'password' => self::STRONG,
            'password_confirmation' => self::STRONG,
        ])->assertRedirect(route('login'));

        $this->assertSame(0, $user->tokens()->count());
        $this->assertSame(0, $user->refresh()->failed_login_attempts);
    }

    public function test_weak_password_is_rejected_on_reset(): void
    {
        $user = User::factory()->create();
        $token = Password::broker()->createToken($user);

        $this->post(route('password.update'), [
            'token' => $token,
            'email' => $user->email,
            'password' => 'passwordpassword',
            'password_confirmation' => 'passwordpassword',
        ])->assertSessionHasErrors('password');
    }

    public function test_reset_fails_with_invalid_token(): void
    {
        $user = User::factory()->create();

        $this->post(route('password.update'), [
            'token' => 'bogus-token',
            'email' => $user->email,
            'password' => self::STRONG,
            'password_confirmation' => self::STRONG,
        ])->assertSessionHasErrors('email');
    }

    public function test_user_can_change_own_password(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get(route('password.change.edit'))->assertOk();

        $this->actingAs($user)->put(route('password.change.update'), [
            'current_password' => 'password',
            'password' => self::STRONG,
            'password_confirmation' => self::STRONG,
        ])->assertSessionHasNoErrors();

        $this->assertTrue(Hash::check(self::STRONG, $user->refresh()->password));
        $this->assertDatabaseHas('audit_logs', ['event' => 'password_changed']);
    }

    public function test_password_change_requires_current_password(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->put(route('password.change.update'), [
            'current_password' => 'not-the-password',
            'password' => self::STRONG,
            'password_confirmation' => self::STRONG,
        ])->assertSessionHasErrors('current_password');
    }
}
