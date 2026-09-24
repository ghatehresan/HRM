<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Models\User;
use App\Notifications\AccountLockedNotification;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\ViewErrorBag;
use Tests\TestCase;

final class AuthenticationTest extends TestCase
{
    public function test_login_page_renders(): void
    {
        $this->get(route('login'))->assertOk()->assertSee('ورود');
    }

    public function test_authenticated_users_are_redirected_from_login(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get(route('login'))->assertRedirect(route('home'));
    }

    public function test_user_can_login_with_correct_credentials(): void
    {
        $user = User::factory()->create();

        $this->post(route('login.store'), ['email' => $user->email, 'password' => 'password'])
            ->assertRedirect(route('home'));

        $this->assertAuthenticatedAs($user);
        $this->assertNotNull($user->refresh()->last_login_at);
        $this->assertDatabaseHas('audit_logs', ['event' => 'login', 'user_id' => $user->id]);
    }

    public function test_login_fails_with_wrong_password(): void
    {
        $user = User::factory()->create();

        $this->post(route('login.store'), ['email' => $user->email, 'password' => 'wrong-password'])
            ->assertSessionHasErrors('email');

        $this->assertGuest();
        $this->assertSame(1, $user->refresh()->failed_login_attempts);
        $this->assertDatabaseHas('audit_logs', ['event' => 'login_failed']);
    }

    public function test_unknown_email_and_inactive_account_share_the_generic_failure(): void
    {
        $inactive = User::factory()->inactive()->create();

        $this->post(route('login.store'), ['email' => 'nobody@example.com', 'password' => 'whatever-password'])
            ->assertSessionHasErrors('email');
        $unknownMessage = $this->firstSessionError('email');

        $this->post(route('login.store'), ['email' => $inactive->email, 'password' => 'password'])
            ->assertSessionHasErrors('email');
        $inactiveMessage = $this->firstSessionError('email');

        // Indistinguishable responses: login cannot enumerate accounts.
        $this->assertSame('مشخصات ورود اشتباه است.', $unknownMessage);
        $this->assertSame($unknownMessage, $inactiveMessage);
        $this->assertDatabaseHas('audit_logs', ['event' => 'login_inactive']);
    }

    public function test_account_locks_after_five_failures_and_recovers(): void
    {
        $this->withoutMiddleware(ThrottleRequests::class);
        Notification::fake();

        $user = User::factory()->create();

        for ($i = 0; $i < 5; $i++) {
            $this->post(route('login.store'), ['email' => $user->email, 'password' => 'wrong']);
        }

        $this->assertTrue($user->refresh()->isLockedOut());
        Notification::assertSentTo($user, AccountLockedNotification::class);
        $this->assertDatabaseHas('audit_logs', ['event' => 'login_locked']);

        // Even the correct password is refused while locked.
        $this->post(route('login.store'), ['email' => $user->email, 'password' => 'password'])
            ->assertSessionHasErrors('email');
        $this->assertGuest();

        // First tier lasts 60 seconds; travel past it.
        $this->travel(61)->seconds();

        $this->post(route('login.store'), ['email' => $user->email, 'password' => 'password'])
            ->assertRedirect(route('home'));

        $this->assertAuthenticated();
        $this->assertSame(0, $user->refresh()->failed_login_attempts);
        $this->assertNull($user->locked_until);
    }

    public function test_login_is_throttled(): void
    {
        $user = User::factory()->create();

        for ($i = 0; $i < 5; $i++) {
            $this->post(route('login.store'), ['email' => $user->email, 'password' => 'wrong']);
        }

        $this->post(route('login.store'), ['email' => $user->email, 'password' => 'wrong'])
            ->assertStatus(429);
    }

    public function test_user_can_logout(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('logout'))->assertRedirect(route('login'));

        $this->assertGuest();
        $this->assertDatabaseHas('audit_logs', ['event' => 'logout', 'user_id' => $user->id]);
    }

    private function firstSessionError(string $key): ?string
    {
        $errors = session('errors');

        return $errors instanceof ViewErrorBag ? $errors->first($key) : null;
    }
}
