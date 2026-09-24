<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * DATABASE.md §3.1 plus two lockout columns (failed_login_attempts,
     * locked_until) required by SECURITY.md §2 progressive lockout.
     * The addition is documented in DATABASE.md §3.1.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('is_active')->default(true)->after('remember_token');
            $table->dateTime('last_login_at')->nullable()->after('is_active');
            $table->dateTime('password_changed_at')->nullable()->after('last_login_at');
            $table->boolean('mfa_enforced')->default(false)->after('password_changed_at');
            $table->unsignedInteger('failed_login_attempts')->default(0)->after('mfa_enforced');
            $table->dateTime('locked_until')->nullable()->after('failed_login_attempts');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'is_active',
                'last_login_at',
                'password_changed_at',
                'mfa_enforced',
                'failed_login_attempts',
                'locked_until',
            ]);
        });
    }
};
