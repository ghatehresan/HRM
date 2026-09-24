# منابع انسانی قطعه‌رسان — GhatehResan HRM

سیستم جامع مدیریت منابع انسانی مجموعهٔ قطعه‌رسان؛ فارسی، راست‌به‌چپ،
Production-ready و یکپارچه با اکوسیستم موجود (برند، سامانهٔ مدیریت، وب‌سایت).

> **وضعیت: Milestone 1 — بنیان (Foundation)**
> معماری، Design System، تقویم شمسی و اسکافولد امن آماده است.
> احراز هویت و ماژول‌های HR از Milestone 2 آغاز می‌شوند.

## Stack

| لایه | فناوری |
|---|---|
| Backend | PHP 8.3+ · **Laravel 13** |
| Database | MySQL / MariaDB (`utf8mb4_persian_ci`) در **همهٔ** محیط‌ها |
| Frontend | Blade + Alpine.js + Tailwind CSS v4 (Vite) |
| Auth | Session/Cookie + MFA (TOTP) + Sanctum tokens |
| Queue / Cache / Session | درایور `database` (بدون Redis — رجوع به `AUDIT.md` §۲۲٫۵) |
| Test | PHPUnit 12 · Pint · PHPStan · Playwright (از M2) |
| CI | GitHub Actions (PHP 8.3/8.4 + Node 22) |

## شروع سریع (XAMPP روی ویندوز)

```powershell
# ۱) پیش‌نیاز: XAMPP با PHP 8.3 یا بالاتر + Composer + Node.js 20+
# ۲) در MySQL یک دیتابیس بسازید:
#    CREATE DATABASE hrm CHARACTER SET utf8mb4 COLLATE utf8mb4_persian_ci;

cd C:\xampp\htdocs\hrm
composer install
copy .env.example .env
php artisan key:generate
php artisan migrate
npm install
npm run build
php artisan serve   # → http://localhost:8000
```

> ⚠️ **نکتهٔ امنیتی حیاتی:** DocumentRoot باید دقیقاً روی پوشهٔ `public/`
> تنظیم شود، وگرنه `.env` و مدارک پرسنلی از طریق وب قابل دسترسی خواهند بود.
> راهنمای کامل: [`ENVIRONMENT.md`](ENVIRONMENT.md)

## فرمان‌های روزمره

| فرمان | کاربرد |
|---|---|
| `php artisan serve` | اجرای محلی |
| `php artisan test` | اجرای تست‌ها |
| `TZ=UTC php artisan test && TZ=Asia/Tehran php artisan test` | تست زیر هر دو منطقهٔ زمانی |
| `vendor/bin/pint --test` | بررسی استایل کد |
| `vendor/bin/phpstan analyse` | تحلیل استاتیک (سطح ۵) |
| `npm run dev` / `npm run build` | توسعه / بیلد فرانت‌اند |
| `composer audit` | بررسی آسیب‌پذیری وابستگی‌ها |

## نقشهٔ مستندات

| سند | محتوا |
|---|---|
| [`AUDIT.md`](AUDIT.md) | Audit کامل سه Repository موجود + تصمیمات ثبت‌شده |
| [`ARCHITECTURE.md`](ARCHITECTURE.md) | معماری لایه‌ای، Bounded Contextها، قراردادها |
| [`DATABASE.md`](DATABASE.md) | قرارداد کامل Schema (مرجع Milestoneهای ۲ تا ۱۲) |
| [`SECURITY.md`](SECURITY.md) | مدل تهدید و کنترل‌های امنیتی |
| [`ENVIRONMENT.md`](ENVIRONMENT.md) | محیط‌ها، نصب XAMPP، استقرار |
| [`BRAND.md`](BRAND.md) | نگاشت توکن‌های برند به Design System |
| [`TESTING.md`](TESTING.md) | استراتژی تست + ماتریس تست‌های دسترسی |
| [`AGENTS.md`](AGENTS.md) | راهنمای ایجنت‌ها و توسعه‌دهندگان جدید |

اسناد `API.md` · `AUTHENTICATION.md` · `AUTHORIZATION.md` · `INTEGRATION.md` ·
`DEPLOYMENT.md` · `BACKUP.md` در Milestoneهای بعدی (هم‌زمان با پیاده‌سازی) افزوده می‌شوند.

## Milestoneها

| # | Milestone | وضعیت |
|---|---|---|
| M0 | Repository Audit | ✅ کامل (`AUDIT.md`) |
| M1 | Architecture & Design System | ✅ کامل (این نسخه) |
| M2 | Authentication + Authorization | ⬜ بعدی |
| M3–M12 | Organization → Integrations | ⬜ طبق نقشهٔ راه `AUDIT.md` §۲۱ |

## قوانین طلایی (خلاصه)

1. **ذخیره UTC، نمایش شمسی** — Jalali فقط در لایهٔ نمایش.
2. **مجوز در Backend اِعمال می‌شود** — Frontend فقط نمایش را پنهان می‌کند.
3. **دادهٔ حساس هرگز در API عمومی** — حقوق، کد ملی، حساب بانکی، مدارک.
4. **بدون دادهٔ واقعی در Seed** — همهٔ داده‌های نمونه Fake هستند.
5. **هیچ وابستگی سختی به Redis** — باید روی XAMPP و هاست اشتراکی کار کند.
6. **بعد از هر Milestone:** Test · Lint · Type check · Build · Security check · Docs.
