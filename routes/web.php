<?php

use App\Http\Controllers\Admin\AuditLogController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\MfaChallengeController;
use App\Http\Controllers\Auth\MfaSetupController;
use App\Http\Controllers\Auth\NewPasswordController;
use App\Http\Controllers\Auth\PasswordController;
use App\Http\Controllers\Auth\PasswordResetLinkController;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
})->name('home');

Route::get('/health', function () {
    $db = 'ok';

    try {
        DB::connection()->getPdo();
    } catch (Throwable $e) {
        report($e);
        $db = 'unreachable';
    }

    return response()->json([
        'app' => config('app.name'),
        'env' => app()->environment(),
        'php' => PHP_VERSION,
        'laravel' => app()->version(),
        'db' => $db,
        'time_utc' => now('UTC')->toIso8601String(),
    ]);
})->name('health');

// Public auth pages (authenticated visitors are bounced to home by
// the controllers themselves — see AUTHENTICATION.md §1).
Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
Route::post('/login', [AuthenticatedSessionController::class, 'store'])->middleware('throttle:5,1')->name('login.store');
Route::get('/forgot-password', [PasswordResetLinkController::class, 'create'])->name('password.request');
Route::post('/forgot-password', [PasswordResetLinkController::class, 'store'])->middleware('throttle:5,1')->name('password.email');
Route::get('/reset-password/{token}', [NewPasswordController::class, 'create'])->name('password.reset');
Route::post('/reset-password', [NewPasswordController::class, 'store'])->middleware('throttle:5,1')->name('password.update');

Route::middleware(['auth', 'is-active', 'session-fresh'])->group(function () {
    Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');

    // MFA completion paths: reachable with an UNVERIFIED session.
    Route::get('/mfa/setup', [MfaSetupController::class, 'show'])->name('mfa.setup');
    Route::post('/mfa/setup/confirm', [MfaSetupController::class, 'confirm'])->middleware('throttle:5,1')->name('mfa.setup.confirm');
    Route::get('/mfa/challenge', [MfaChallengeController::class, 'show'])->name('mfa.challenge');
    Route::post('/mfa/challenge', [MfaChallengeController::class, 'store'])->middleware('throttle:5,1')->name('mfa.challenge.store');

    // Everything below requires a verified (MFA-complete) session.
    Route::middleware('mfa-required')->group(function () {
        Route::post('/mfa/codes/regenerate', [MfaSetupController::class, 'regenerateCodes'])->name('mfa.codes.regenerate');
        Route::delete('/mfa', [MfaSetupController::class, 'destroy'])->name('mfa.destroy');

        Route::get('/password/change', [PasswordController::class, 'edit'])->name('password.change.edit');
        Route::put('/password/change', [PasswordController::class, 'update'])->name('password.change.update');

        Route::prefix('admin')->name('admin.')->group(function () {
            Route::get('/users', [AdminUserController::class, 'index'])->middleware('permission:users.view')->name('users.index');
            Route::get('/users/create', [AdminUserController::class, 'create'])->middleware('permission:users.manage')->name('users.create');
            Route::post('/users', [AdminUserController::class, 'store'])->middleware('permission:users.manage')->name('users.store');
            Route::get('/users/{user}/edit', [AdminUserController::class, 'edit'])->middleware('permission:users.manage')->name('users.edit');
            Route::put('/users/{user}', [AdminUserController::class, 'update'])->middleware('permission:users.manage')->name('users.update');

            Route::get('/roles', [RoleController::class, 'index'])->middleware('permission:roles.view')->name('roles.index');
            Route::get('/roles/create', [RoleController::class, 'create'])->middleware('permission:roles.manage')->name('roles.create');
            Route::post('/roles', [RoleController::class, 'store'])->middleware('permission:roles.manage')->name('roles.store');
            Route::get('/roles/{role}/edit', [RoleController::class, 'edit'])->middleware('permission:roles.manage')->name('roles.edit');
            Route::put('/roles/{role}', [RoleController::class, 'update'])->middleware('permission:roles.manage')->name('roles.update');

            Route::get('/audit-logs', [AuditLogController::class, 'index'])->middleware('permission:audit.view')->name('audit-logs.index');
        });
    });
});
