# AUTHORIZATION — مجوز HRM (RBAC)

**Milestone 2** · جزئیات پیاده‌سازی §۳ `SECURITY.md`.

مدل: کاربر ←→ نقش ←→ مجوز (جداول `roles`، ‏`permissions`، ‏`role_user`، ‏`permission_role`).
نام مجوزها رشته‌های نقطه‌دار `resource.action`‌اند و **همان رشته** در هر چهار لایه
استفاده می‌شود تا Gate و Middleware و Policy و Blade ‏`@can` همیشه توافق داشته باشند:

```php
// app/Providers/AppServiceProvider.php
Gate::before(function (User $user, string $ability): ?bool {
    return $user->hasPermission($ability) ? true : null;
});
```

`null` یعنی «به Policy واگذار کن» و در نبود Policy یعنی deny. نقش `super-admin`
در `hasPermission` همیشه true می‌گیرد (bypass در سطح Gate، بدون نیاز به سطر permission).
سه‌لایهٔ اجباری §۳ SECURITY امروز دو لایه‌اش فعال است (Middleware → Policy)؛
لایهٔ سوم (Query Scope ‏`self/team/department/company/all`) با مدل‌های داده از M4 می‌آید.

---

## ۱. نقش‌های پیش‌فرض (Seeder)

| slug | کاربرد M2 |
|---|---|
| `super-admin` | bypass همه‌چیز؛ فقط برای مالک/DevOps؛ ساخت/ویرایش آن از UI محافظت می‌شود |
| `hr-admin` | هر ۵ مجوز M2 (مدیر کامل کاربران/نقش‌ها/لاگ) |
| `hr-manager` | فقط مشاهده (`users.view` + `audit.view`) |
| `auditor` | فقط مشاهده (`users.view` + `audit.view`) |
| `department-manager` · `team-lead` · `employee` · `finance` | فعلاً بدون مجوز M2؛ مجوزهای کاری‌شان با ماژول‌ها (M3+) می‌آید |

سیدرها idempotent‌انـد (`firstOrCreate` + `syncWithoutDetaching`) و اجرای دوباره‌شان
تغییرات دستی را خراب نمی‌کند. `finance` از روز اول در نقش‌های ملزم به MFA است
(دسترسی آینده به حقوق) — رجوع به `AUTHENTICATION.md` §۵.

## ۲. مجوزهای M2

| نام | گروه | معنی |
|---|---|---|
| `users.view` | `user` | مشاهدهٔ فهرست/جزئیات کاربران |
| `users.manage` | `user` | ساخت و ویرایش کاربران و نقش‌هایشان |
| `roles.view` | `role` | مشاهدهٔ نقش‌ها |
| `roles.manage` | `role` | ساخت و ویرایش نقش‌ها و مجوزها |
| `audit.view` | `audit` | مشاهده و جست‌وجوی لاگ حسابرسی |

قرارداد نام‌گذاری (برای M3+): ‏`resource.action` با resource مفرد انگلیسی
(`employee.salary.view`) و action از مجموعهٔ ‏`view/manage/approve/export/...`.
ستون `group` نام مفرد resource است (`user`، ‏`role`، ‏`audit`) و فقط برای نمایش
دسته‌بندی‌شده در UI مصرف می‌شود.

## ۳. Policyها

| Policy | نگاشت |
|---|---|
| `UserPolicy` | ‏`viewAny/view ← users.view` · ‏`create/update ← users.manage` · ‏`delete ← همیشه false` |
| `RolePolicy` | ‏`viewAny/view ← roles.view` · ‏`create/update ← roles.manage` · ‏`delete ← همیشه false` |
| `AuditLogPolicy` | ‏`viewAny/view ← audit.view` (update/delete متد ندارند — مدل append-only است) |

`delete=false` عمدی است: کاربر غیرفعال (`is_active`) می‌شود نه حذف؛ نقش سیستمی
حذف نمی‌شود. نکتهٔ ظریف: چون Gate::before برای super-admin روی **هر** ability ‏
true برمی‌گرداند، `can('delete')` برای سوپرادمین true است — اما چون هیچ مسیر،
دکمه یا فراخوانی delete وجود ندارد، این true هیچ‌وقت به حذف واقعی نمی‌رسد.

## ۴. Middlewareها

| alias | کلاس | کار |
|---|---|---|
| `permission:name` | `CheckPermission` | 403 اگر کاربر مجوز را نداشته باشد |
| `role:slug` | `CheckRole` | 403 اگر نقش را نداشته باشد |
| `is-active` | `EnsureUserIsActive` | بیرون‌اندازی کاربر غیرفعال‌شده + Audit ‏`session_revoked` |
| `session-fresh` | `EnsureSessionFresh` | timeout مطلق ۱۲ ساعته + Audit ‏`session_expired` |
| `mfa-required` | `RequireMfa` | هدایت به ثبت‌نام/چالش MFA |
| `can:ability,Model` | فریم‌ورک | بررسی Policy روی مسیر (ability همان رشتهٔ مجوز است) |

ترتیب استک مسیرهای محافظت‌شده: `auth` → ‏`is-active` → ‏`session-fresh` → (داخل‌تر) `mfa-required` →
‏`permission:…` روی هر مسیر ادمین. پاسخ JSON برای API ‏(401/403 با JSON) و redirect برای وب به‌صورت خودکار
از `expectsJson` انتخاب می‌شود.

## ۵. پنل ادمین

مسیرهای `/admin/users` (فهرست/جست‌وجو/ساخت/ویرایش)، `/admin/roles` و
`/admin/audit-logs` (فقط خواندن + فیلتر event/actor/بازهٔ شمسی). نگهبان‌های
خود-محافظتی در `UserService`/`RoleService` (نه در کنترلر، تا از هیچ مسیری
دور زده نشوند):

- هیچ‌کس نمی‌تواند خودش را غیرفعال کند؛
- آخرین نقش `super-admin` خود را نمی‌توان از خود گرفت؛
- slug ‏`super-admin` از UI ساخته/ویرایش نمی‌شود؛
- تغییر رمز توسط ادمین همهٔ توکن‌های API کاربر را باطل می‌کند.

هر عملیات Audit می‌شود: ‏`user_created` · ‏`user_updated` · ‏`role_created` · ‏`role_updated`.

## ۶. افزودن مجوز/نقش جدید (M3+)

1. سطر مجوز در `PermissionSeeder` (نام `resource.action` + گروه مفرد)؛
2. اتصال به نقش‌ها در همان سیدر (`syncWithoutDetaching`)؛
3. Policy متد متناظر + `can:` روی مسیرها (+ اسکوپ کوئری از M4)؛
4. تست Policy (مجاز/غیرمجاز/خارج از scope) + سطر ماتریس در `TESTING.md` §۵؛
5. به‌روزرسانی همین سند (§۲/§۳).
