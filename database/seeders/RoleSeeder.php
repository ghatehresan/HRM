<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    /**
     * The 8 default roles (DATABASE.md §3.2). Idempotent: safe to
     * re-run on every deploy; custom roles are never touched.
     */
    public function run(): void
    {
        $roles = [
            ['مدیر سیستم', 'super-admin', 'دسترسی کامل؛ bypass در User::hasPermission'],
            ['راهبر منابع انسانی', 'hr-admin', 'مدیریت کاربران، نقش‌ها و همهٔ ماژول‌های HR'],
            ['مدیر منابع انسانی', 'hr-manager', 'مشاهده و تأیید در سطح HR'],
            ['مدیر دپارتمان', 'department-manager', 'مدیریت تیم و تأییدهای دپارتمان خود'],
            ['سرپرست تیم', 'team-lead', 'مشاهده و اقدام محدود در سطح تیم'],
            ['کارمند', 'employee', 'سلف‌سرویس شخصی'],
            ['مالی', 'finance', 'حقوق و دادهٔ مالی (از M12)'],
            ['حسابرس', 'auditor', 'مشاهدهٔ فقط‌خواندنی کاربران و لاگ حسابرسی'],
        ];

        foreach ($roles as [$name, $slug, $description]) {
            Role::firstOrCreate(['slug' => $slug], [
                'name' => $name,
                'description' => $description,
            ]);
        }
    }
}
