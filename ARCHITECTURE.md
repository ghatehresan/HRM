# ARCHITECTURE — معماری HRM قطعه‌رسان

**Milestone 1** · تاریخ: ۲ مهر ۱۴۰۵ (۲۴ سپتامبر ۲۰۲۶)
وضعیت: **تأیید ضمنی با تصمیمات D-1 · D-2 · D-4** (رجوع به `AUDIT.md` §۲۵)

---

## ۱. تصمیمات بنیان (ADR خلاصه)

| # | تصمیم | انتخاب | دلیل |
|---|---|---|---|
| A-1 | Backend | **PHP 8.3+ + Laravel 13** | تنها زبان سمت‌سرور اکوسیستم PHP است؛ Laravel به‌صورت بومی RBAC، Queue، Validation، Encryption و Signed URL دارد؛ روی XAMPP و هاست اشتراکی اجرا می‌شود. نسخهٔ ۱۳ آخرین پایدار در زمان اسکافولد است (۱۳٫۳۳، تأیید با GitHub API). |
| A-2 | Database | **MySQL/MariaDB** در همهٔ محیط‌ها | یکسان‌سازی رفتار dev و prod؛ `utf8mb4_persian_ci` قبلاً در `config.php` سامانهٔ مدیریت توصیه شده؛ PostgreSQL روی XAMPP نیست. |
| A-3 | Frontend | **Blade + Alpine.js + Tailwind v4** | بدون نیاز به Node در توسعه؛ هم‌خوان با مهارت PHP تیم؛ Tailwind v4 همان نسخهٔ وب‌سایت. ارتقا به Inertia/React بعداً بدون بازنویسی Backend ممکن است. |
| A-4 | Cache/Queue/Session | درایور **`database`** | XAMPP و اکثر هاست‌های اشتراکی Redis ندارند (`AUDIT.md` §۲۲٫۵ X-1). هیچ کدی نباید به Redis وابسته باشد. |
| A-5 | Timezone | ذخیره **UTC**، نمایش **Asia/Tehran + شمسی** | رفع نقص `localtime` در سامانهٔ مدیریت (`AUDIT.md` H-3). |
| A-6 | UI Language | فارسی، RTL، وزیرمتن خودمیزبانی‌شده | الزام کتاب برند؛ بدون CDN خارجی. |

## ۲. معماری لایه‌ای

```
┌─────────────────────────────────────────────────────────────┐
│ Presentation                                                │
│  Blade views (RTL) · Alpine.js · Tailwind v4 @theme         │
│  /api/v1/* (از M2 — روی همان Application Layer)            │
└────────────────────────┬────────────────────────────────────┘
                         ▼
┌─────────────────────────────────────────────────────────────┐
│ HTTP — Controllers (نازک)                                   │
│  FormRequest (اعتبارسنجی) · Policy (مجوز) · Resource (خروجی) │
└────────────────────────┬────────────────────────────────────┘
                         ▼
┌─────────────────────────────────────────────────────────────┐
│ Application — Services / Actions                            │
│  منطق کسب‌وکار · تراکنش · رویداد · ثبت Audit                │
└────────────────────────┬────────────────────────────────────┘
                         ▼
┌─────────────────────────────────────────────────────────────┐
│ Domain — Bounded Contexts (بخش ۳)                           │
└────────────────────────┬────────────────────────────────────┘
                         ▼
┌─────────────────────────────────────────────────────────────┐
│ Infrastructure                                              │
│  Eloquent · Migration · Storage خصوصی · Queue · Mail/SMS   │
│  Jalali/Persian (app/Support) · Encryption                  │
└─────────────────────────────────────────────────────────────┘
```

### قوانین لایه‌ها

- Controller فقط: اعتبارسنجی → فراخوانی Service → بازگرداندن View/Resource.
- هیچ کوئری Eloquent مستقیمی در Controller و Blade مجاز نیست.
- منطق کسب‌وکار فقط در Application/Domain؛ هرگز در Migration یا Seeder.
- خروجی API فقط از طریق Resource/Transformer (ضد over-fetching).

## ۳. Bounded Contextها

| Context | Milestone | موجودیت‌های اصلی |
|---|---|---|
| Identity | M2 | User · Role · Permission · MFAMethod · Token |
| Organization | M3 | Company · Branch · Department · Position |
| People | M4 | Employee (+Contact/Bank/Compensation) |
| Contracts | M8 | ContractType · Contract |
| Time | M6–M7 | Shift · Attendance · LeaveType/Request/Balance · Holiday |
| Talent (ATS) | M9 | JobOpening · Applicant · Application · Interview · Evaluation · Offer |
| Documents | M8 | DocumentType · Document · DocumentVersion |
| Performance | M11 | Goal · ReviewCycle · Review · Feedback |
| Training | M11 | Course · Training · Certificate |
| Assets | M11 | AssetCategory · Asset · AssetAssignment |
| Requests | M10 | RequestType · Request · Approval · Workflow |
| Notification | M5/M10 | Notification · Preference |
| Reporting | M11 | SavedReport · ExportJob (+ Read Models) |
| Audit | M2 | AuditLog (append-only) |

**قانون تفکیک:** `User` (هویت ورود) ≠ `Employee` (پروفایل پرسنلی).
ارتباط ۱:۱ و nullable از سمت Employee. این تفکیک استخراج بعدی
Identity به سرویس مستقل را بدون migration داده‌ای ممکن می‌کند.

## ۴. قراردادهای داده (خلاصه — جزئیات در `DATABASE.md`)

| موضوع | قرارداد |
|---|---|
| Collation | `utf8mb4_persian_ci` |
| تاریخ مدنی (تولد، استخدام) | `DATE` بدون timezone |
| رویداد (ورود/خروج، ثبت) | `DATETIME` به UTC |
| نمایش | تبدیل به شمسی + Asia/Tehran فقط در Presentation |
| مبالغ | `DECIMAL(15,2)` + ستون `currency` (پیش‌فرض `IRR`) |
| حذف | Soft Delete روی موجودیت‌های حساس؛ Audit هرگز حذف نمی‌شود |
| فیلد حساس | ستون رمزنگاری‌شده (national_id، bank_account) یا جدول جدا (salary) |
| فایل | جدول `documents` + دیسک خصوصی خارج از webroot + URL امضاشده |
| ایندکس | روی همهٔ FKها + فیلدهای جست‌وجو + `(company_id, department_id, status)` |

## ۵. ساختار پوشه‌ها

```
app/
├── Http/Controllers/     → کنترلرهای نازک (از M2)
├── Http/Requests/        → FormRequestها (از M2)
├── Http/Resources/       → API Resources (از M2)
├── Models/               → مدل‌های Eloquent (از M2)
├── Policies/             → سیاست‌های مجوز (از M2)
├── Services/             → سرویس‌های Application (از M2)
└── Support/              → کد خالص بدون وابستگی (Jalali، PersianNumbers) ✅ M1
config/hrm.php            → تنظیمات محصول (display timezone، نام‌ها) ✅ M1
database/migrations/      → مهاجرت‌ها (users/cache/jobs از اسکلت) ✅ M1
resources/
├── tokens/design-tokens.json  → منبع حقیقت Design Tokens ✅ M1
├── css/app.css                → تم Tailwind v4 + فونت‌ها ✅ M1
├── js/app.js                  → Alpine + نرمال‌سازی ارقام ✅ M1
└── views/
    ├── components/layouts/app.blade.php  → شل RTL با سایدبار (`<x-layouts.app>`) ✅ M1
    ├── components/*           → badge/btn/card/empty/flash/page-head/stat ✅ M1
    └── welcome.blade.php      → صفحهٔ خانه ✅ M1
public/
├── fonts/     → وزیرمتن woff2 خودمیزبانی‌شده (OFL) ✅ M1
└── brand/     → لوگوهای SVG (بدون @import خارجی) ✅ M1
routes/web.php    → / و /health ✅ M1
routes/console.php → زمان‌بند (خالی تا M10، دوحالته) ✅ M1
tests/
├── Unit/   → Jalali · PersianNumbers · BrandTokens ✅ M1
└── Feature/ → Welcome (home + health) ✅ M1
```

## ۶. Frontend

- **موتور:** Blade + Alpine.js (تعامل‌های سبک) + Tailwind v4.
- **توکن‌ها:** `resources/tokens/design-tokens.json` → `@theme` در `app.css`
  (راستی‌آزمایی خودکار با `BrandTokensTest`).
- **فونت:** Vazirmatn ‏(۴۰۰/۵۰۰/۷۰۰/۹۰۰) از `/fonts` — هرگز CDN.
- **RTL:** `dir="rtl"` + ویژگی‌های منطقی CSS (`ms-*`، `ps-*`)؛ ورودی‌های
  عددی/کد همیشه LTR (`.ltr-input`).
- **Breakpoint موبایل:** `900px` (واریانت `tablet:`) — مطابق سامانهٔ مدیریت.
- **دارایی‌ها:** در CI ساخته می‌شوند (`npm run build`)؛ خروجی `public/build`
  در مخزن commit نمی‌شود؛ Production به Node نیاز ندارد.

## ۷. زمان‌بند دوحالته (X-2)

روی هاست اشتراکی نه cron تضمین‌شده هست نه worker بلندمدت. از M10:

1. اگر cron در دسترس بود → `php artisan schedule:run` هر دقیقه.
2. وگرنه → مسیر HTTP محافظت‌شده با توکن (`POST /schedule/run`)
   که یک سرویس cron خارجی صدا می‌زند.
3. همهٔ Jobها **idempotent** (بدون قفل‌گرفتن فرضی، با کلید یکتایی).

## ۸. امنیت (خلاصه — جزئیات در `SECURITY.md`)

- مجوز سه‌لایه در Backend: Middleware → Policy → Query Scope.
- دادهٔ حساس: رمزنگاری در سطح کاربرد + Permission مستقل + حذف از خروجی پیش‌فرض.
- فایل‌ها: دیسک خصوصی + نام تصادفی + بررسی magic bytes + URL امضاشدهٔ کوتاه‌مدت.
- Audit Log append-only برای همهٔ عملیات حساس.
- Rate limiting روی auth و مسیرهای عمومی؛ هدرهای امنیتی؛ CSP.

## ۹. کیفیت (خلاصه — جزئیات در `TESTING.md`)

- بعد از هر Milestone: Test · Pint · PHPStan · Build · Security check · Docs.
- تست‌ها زیر **دو** منطقهٔ زمانی اجرا می‌شوند (`TZ=UTC` و `TZ=Asia/Tehran`).
- ماتریس تست دسترسی (M46) از M2 اجباری است و در CI اجرا می‌شود.

## ۱۰. نقشهٔ استقرار

XAMPP (توسعه) → هاست اشتراکی/VPS (staging/production).
جزئیات کامل، شامل پیکربندی DocumentRoot و سناریوی بدون SSH، در `ENVIRONMENT.md`.
