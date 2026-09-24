# TESTING — استراتژی تست

**Milestone 1**

---

## ۱. هرم تست

```
        ┌─────────┐
        │   E2E   │  Playwright — از M2 (ورود، ساخت کارمند، مرخصی، آپلود، دسترسی)
        ├─────────┤
        │ Feature │  HTTP/API — هر endpoint + هر Policy
        ├─────────┤
        │  Unit   │  منطق خالص (Jalali، اعداد، Workflow، محاسبات)
        └─────────┘
        + Static: Pint (استایل) · PHPStan سطح ۵→۸ · composer/npm audit
```

## ۲. اجرای تست‌ها

```bash
php artisan test                                   # همه
TZ=UTC php artisan test                            # زیر UTC
TZ=Asia/Tehran php artisan test                    # زیر Asia/Tehran (اجباری در CI)
php artisan test --filter=JalaliTest               # گزینشی
vendor/bin/pint --test                             # استایل
vendor/bin/phpstan analyse                         # تحلیل استاتیک
npm run build                                      # بیلد فرانت‌اند
```

**چرا دو منطقهٔ زمانی؟** کمک‌تابع‌های تاریخ باید نسبت به DST مصون باشند
(رجوع به `Jalali::monthRange`). CI هر دو را اجرا می‌کند؛ اختلاف نتیجه = باگ.

## ۳. وضعیت M1

| مجموعه | تعداد | پوشش |
|---|---|---|
| `tests/Unit/JalaliTest` | ۷ تست | anchorهای نوروز، round-trip، فرمت‌ها، parse، بازهٔ ماه، today |
| `tests/Unit/PersianNumbersTest` | ۵ تست | تبدیل ارقام، money، toInt، plain |
| `tests/Unit/BrandTokensTest` | ۲ تست | ضد انحراف توکن↔تم + قاعدهٔ ۱۰٪ نارنجی |
| `tests/Feature/WelcomeTest` | ۲ تست | رندر شل RTL + ساختار `/health` |
| `tests/Unit/JalaliTimezoneTest` | ۴ تست | wall-clock تهران، عبور از نیمه‌شب، ورودی‌های DateTime/int، رد ورودی خراب |
| `tests/Feature/Auth/*` | ۳۱ تست | ورود/خروج، throttle، قفل پلکانی، ریست رمز، تغییر رمز، StrongPassword، چرخهٔ کامل MFA |
| `tests/Feature/Rbac/RbacTest` | ۷ تست | bypass سوپرادمین، 403، سیاست‌ها، خروج کاربر غیرفعال، timeout مطلق نشست |
| `tests/Feature/Admin/*` | ۱۶ تست | CRUD کاربران/نقش‌ها، نگهبان‌های self، جست‌وجو، فیلترها و immutability لاگ |
| `tests/Feature/Api/ApiAuthTest` | ۶ تست | صدور/مصرف/ابطال توکن، 401 بدون توکن، MFA روی صدور، توکن منقضی/غیرفعال |
| `tests/Feature/Security/*` | ۹ تست | دروازهٔ ماتریس M46، هشینگ، هدرهای امنیتی |
| `tests/Feature/Database/SeedersTest` | ۲ تست | idempotency سیدرهای هویتی، منع DevSeeder در غیرlocal |

مجموع M2: ۷۵ تست جدید (جمع کل: ۹۱). منطق خالص Jalali زیر PHP 8.3 واقعی
(WASM) با ۷۶/۷۶ assertion در هر دو timezone سبز است (رجوع به §۷).

## ۴. قراردادهای نوشتن تست

- نام تست: `test_<رفتار>_<شرایط>` به انگلیسی؛ دادهٔ نمایشی فارسی.
- Unit خالص (`PHPUnit\Framework\TestCase`) برای کد بدون فریم‌ورک؛
  Feature (`Tests\TestCase`) برای HTTP.
- Factory + `RefreshDatabase` برای تست‌های DB (از M2).
- **ممنوعیت مطلق دادهٔ واقعی** در Seed/فیکسچر/تست (رجوع به `DATABASE.md` §۱۱).
- هر Policy جدید = حداقل ۳ تست (مجاز/غیرمجاز/خارج از scope).
- هر باگ production = ابتدا تست بازتولید قرمز، بعد اصلاح.

## ۵. ماتریس تست دسترسی (MODULE 46 — از M2 اجباری)

انتشار با هر تست قرمز در این فهرست **ممنوع** است:

```
□ کارمند A نمی‌تواند پروفایل کارمند B را ببیند
□ کارمند A نمی‌تواند حقوق کارمند B را ببیند (حتی در JSON)
□ کارمند A نمی‌تواند مدارک کارمند B را ببیند/دانلود کند
□ کارمند A نمی‌تواند اطلاعات خصوصی کارمند B را ببیند
□ مدیر A نمی‌تواند دادهٔ دپارتمان B را ببیند (حتی با حدس ID)
□ کاربر بدون employee.salary.view فیلد salary را در پاسخ نمی‌بیند
□ جست‌وجوی سراسری دادهٔ خارج از scope را افشا نمی‌کند
□ دانلود مستقیم فایل بدون نشست معتبر ناموفق است
□ URL امضاشده پس از انقضا ناموفق است
□ Audit Log قابل حذف/ویرایش از هیچ مسیری نیست
□ کاربر غیرفعال (is_active=false) نمی‌تواند وارد شود
□ مسیرهای /api/v1/* بدون توکن 401 می‌دهند، نه 500/redirect
```

## ۶. E2E (از M3 — Playwright)

جریان‌های حیاتی: ورود/خروج · ساخت کارمند · درخواست مرخصی · تأیید مرخصی ·
آپلود مدرک · بررسی دسترسی (کاربر کم‌مجوز) · تغییر رمز.
اجرا در CI روی هر PR به `main`.

> وضعیت M2: سوئیت E2E نوشته نشد. دلیل: sandbox توسعه نه مرورگر دارد نه
> شبکه برای نصب Playwright، و E2E نوشته‌شدهٔ «نابینا» (بدون حتی یک اجرای
> محلی) ریسک قرمزی دائمی CI را دارد. به‌جایش چرخه‌های حیاتی احراز هویت
> (ورود/خروج/MFA/ریست رمز) با ۳۱ تست Feature پوشش داده شدند و E2E از M3
> با job اختصاصی CI و اجرای محلی اجباری قبل از push برمی‌گردد.

## ۷. راستی‌آزمایی در محیط محدود (سند تاریخی M1)

sandbox توسعه در M1 به Packagist و PHP سیستمی دسترسی نداشت؛ لذا:

1. منطق خالص PHP با **PHP 8.3 واقعی (WASM)** اجرا و ۶۸/۶۸ assertion
   زیر دو timezone پاس شد (این راستی‌آزمایی همان باگ انتظار اشتباه
   طول ماه مهر را گرفت — مستند در تاریخچه).
2. سینتکس همهٔ فایل‌های PHP با `php-parser` بررسی شد.
3. بیلد Vite/Tailwind واقعاً اجرا و حضور توکن‌ها در CSS خروجی تأیید شد.
4. نصب Composer + PHPUnit + Pint + PHPStan کامل در **CI** اجرا می‌شود
   (جایی که شبکه کامل است) و نتیجهٔ آن قبل از هر merge بررسی می‌شود.
