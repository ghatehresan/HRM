# AGENTS.md — راهنمای ایجنت/توسعه‌دهندهٔ HRM قطعه‌رسان

## این مخزن چیست

سیستم منابع انسانی فارسی/RTL روی **Laravel 13 + MySQL + Blade/Alpine/Tailwind v4**.
توسعه روی XAMPP؛ بدون Redis؛ ذخیره UTC و نمایش شمسی.

## قبل از هر تغییری بخوان

1. `ARCHITECTURE.md` — لایه‌ها و قراردادها (الزام‌آور)
2. `DATABASE.md` — قرارداد Schema (انحراف ممنوع بدون به‌روزرسانی سند)
3. `SECURITY.md` — کنترل‌های الزامی
4. `BRAND.md` — توکن‌ها و قوانین بصری (رنگ جدید ممنوع)
5. `TESTING.md` — قرارداد تست + ماتریس دسترسی
6. `AUDIT.md` §۲۵ — تصمیمات ثبت‌شده (D-1…D-4، P-1)

## فرمان‌ها

```bash
php artisan test                                  # تست
TZ=UTC php artisan test && TZ=Asia/Tehran php artisan test
vendor/bin/pint --test                            # استایل (باید سبز باشد)
vendor/bin/phpstan analyse                        # تحلیل استاتیک سطح ۵
npm run build                                     # بیلد فرانت‌اند
composer audit && npm audit --audit-level=high    # امنیت وابستگی‌ها
```

## قوانین کد (خلاصهٔ الزامی)

- Controller نازک؛ منطق در Service؛ کوئری در Controller/Blade **ممنوع**.
- خروجی API فقط از Resource؛ فیلد حساس پیش‌فرض حذف.
- مجوز سه‌لایه در Backend؛ پنهان‌سازی در Frontend کافی نیست.
- `{!! !!}` ممنوع مگر sanitize + کامنت دلیل؛ SQL خام بدون binding ممنوع.
- تاریخ: ذخیره UTC/miladi؛ شمسی فقط در نمایش (`App\Support\Jalali`).
- اعداد: ورودی → لاتین نرمال (`PersianNumbers::toEn`)؛ نمایش → فارسی.
- فایل‌ها: دیسک خصوصی + URL امضاشده؛ هرگز مسیر public مستقیم.
- دادهٔ واقعی در Seed/تست **ممنوع** (نام، کد ملی، حقوق، حساب، آدرس).
- Migration اجراشده ویرایش نمی‌شود؛ تغییر = migration جدید + به‌روزرسانی `DATABASE.md`.
- هیچ وابستگی سختی به Redis؛ همه‌چیز باید روی XAMPP کار کند.

## فرایند Milestone

بعد از هر Milestone، به ترتیب: Test → Pint → PHPStan → Build →
Security check → بازبینی diff → به‌روزرسانی مستندات → commit معنادار (فارسی/انگلیسی).
Featureهای مهم با PR؛ این session روی branch فعلی کار می‌کند — branch عوض نکن.

## اگر sandbox به Packagist/PHP دسترسی نداشت

- سینتکس PHP: `node /tmp/phpcheck/check.mjs <files>`
- اجرای منطق خالص: WASM PHP (`/tmp/wasmtest/run-php.mjs`) + هارنس آینه‌ای تست‌ها
- فرانت‌اند: `npm install && npm run build` (npm معمولاً باز است)
- راستی‌آزمایی نهایی همیشه در **CI** (شبکهٔ کامل) + `gh run watch`
