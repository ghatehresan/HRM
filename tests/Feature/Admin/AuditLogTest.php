<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Models\AuditLog;
use App\Models\User;
use App\Support\Jalali;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Route;
use LogicException;
use Tests\TestCase;

final class AuditLogTest extends TestCase
{
    public function test_index_requires_permission(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('admin.audit-logs.index'))->assertForbidden();

        $auditor = $this->userWithPermissions(['audit.view']);

        $this->actingAs($auditor)
            ->get(route('admin.audit-logs.index'))->assertOk();
    }

    public function test_index_filters_by_event_actor_and_jalali_date(): void
    {
        $auditor = $this->userWithPermissions(['audit.view']);
        $alice = User::factory()->create(['email' => 'alice@example.com']);
        $bob = User::factory()->create(['email' => 'bob@example.com']);
        AuditLog::record('login', $alice);
        AuditLog::record('login_failed', $bob);

        $this->actingAs($auditor);

        $this->assertSame(1, $this->filteredTotal(['event' => 'login']));
        $this->assertSame(1, $this->filteredTotal(['user' => 'bob@example.com']));

        // Jalali day filters use the runtime calendar, never a fixed date.
        $today = Jalali::format(now()->toDateString());
        $tomorrow = Jalali::format(now()->addDay()->toDateString());

        $this->assertSame(2, $this->filteredTotal(['date_from' => $today]));
        $this->assertSame(0, $this->filteredTotal(['date_from' => $tomorrow]));
    }

    public function test_audit_entries_cannot_be_altered_or_deleted(): void
    {
        $user = User::factory()->create();
        $log = AuditLog::record('login', $user);

        $this->assertFalse($log->update(['event' => 'tampered']));
        $this->assertSame('login', $log->refresh()->event);

        try {
            $log->delete();
            $this->fail('AuditLog::delete() must throw.');
        } catch (LogicException $e) {
            $this->assertStringContainsString('append-only', $e->getMessage());
        }

        $this->assertDatabaseHas('audit_logs', ['id' => $log->id, 'event' => 'login']);
    }

    public function test_no_route_mutates_audit_logs(): void
    {
        $this->assertFalse(Route::has('admin.audit-logs.destroy'));
        $this->assertFalse(Route::has('admin.audit-logs.update'));

        $this->put('/admin/audit-logs/1')->assertStatus(405);
        $this->delete('/admin/audit-logs/1')->assertStatus(405);
        $this->patch('/admin/audit-logs/1')->assertStatus(405);
    }

    public function test_sensitive_values_never_enter_audit_payloads(): void
    {
        $user = User::factory()->create();

        $this->post(route('login.store'), ['email' => $user->email, 'password' => 'Sup3r-Secret-Wrong!']);

        $payloads = AuditLog::where('event', 'login_failed')->pluck('new_values')->all();
        $this->assertNotEmpty($payloads);

        foreach ($payloads as $payload) {
            $this->assertStringNotContainsString('Sup3r-Secret', (string) json_encode($payload));
        }
    }

    /**
     * @param  array<string, string>  $filters
     */
    private function filteredTotal(array $filters): int
    {
        $response = $this->get(route('admin.audit-logs.index', $filters))->assertOk();
        $logs = $response->viewData('logs');
        $this->assertInstanceOf(LengthAwarePaginator::class, $logs);

        return $logs->total();
    }
}
