# AUTHENTICATION — احراز هویت HRM

**Milestone 2** · جزئیات پیاده‌سازی §۲ `SECURITY.md`.

سه مسیر ورود: نشست وب (کوکی) برای Blade، توکن Sanctum برای `/api/v1`،
و TOTP به‌عنوان عامل دوم هر دو. منطق در `AuthService` و `MfaService` است؛
کنترلرها نازک‌اند و فقط FormRequest را به سرویس می‌دهند.

---

## ۱. ورود با رمز (وب)

| موضوع | پیاده‌سازی |
|---|---|
| مسیرها | `GET /login` → `login` · `POST /login` → `login.store` · `POST /logout` → `logout` |
| اعتبارسنجی | `LoginRequest`: ایمیل معتبر + رمز الزامی |
| Rate limit | `throttle:5,1` روی `login.store` (۵ درخواست/دقیقه/IP) |
| پیام خطا | همیشه «مشخصات ورود اشتباه است.» — ایمیل ناشناس، رمز اشتباه و حساب غیرفعال **یکسان** پاسخ می‌گیرند (ضد شمارش حساب) |
| ورود موفق | چرخش ID نشست، `auth_at` (ساعت شروع timeout مطلق)، `last_login_at`، صفر شدن شمارندهٔ قفل، Audit `login` |

### قفل پلکانی

۵ تلاش ناموفق متوالی حساب را قفل می‌کند؛ مدت قفل با هر تلاش بیشتر می‌شود
(`config/hrm.php` — `auth.lockout_threshold` و `auth.lockout_durations`):

| تلاش ناموفق | مدت قفل |
|---|---|
| پنجم | ۶۰ ثانیه |
| ششم | ۳۰۰ ثانیه (۵ دقیقه) |
| هفتم | ۹۰۰ ثانیه (۱۵ دقیقه) |
| هشتم به بعد | ۳۶۰۰ ثانیه (۱ ساعت) |

- در قفل بودن، حتی رمز صحیح هم همان خطای عمومی را می‌دهد.
- با رسیدن به آستانه، ایمیل «حساب شما قفل شد» (`AccountLockedNotification`) ارسال و Audit ‏`login_failed`/`login_locked` ثبت می‌شود.
- ورود موفق شمارنده و `locked_until` را پاک می‌کند.

## ۲. سیاست رمز عبور

قانون `App\Rules\StrongPassword` (واحد تست: `StrongPasswordTest`):

1. حداقل **۱۲ کاراکتر**؛
2. نبودن در denylist داخلی (رمزهای معروف مثل `password1234`)؛
3. نبودن بخش محلی ایمیل (۴+ کاراکتر) داخل رمز؛
4. امتیاز **zxcvbn ‏≥ ۳**.

اعمال روی: ساخت کاربر توسط ادمین، ریست رمز، تغییر رمز توسط خود کاربر.
هر تغییر موفق `password_changed_at` را به‌روز می‌کند و — در ریست و تغییر ادمین —
همهٔ توکن‌های API کاربر را باطل می‌کند.

تغییر رمز (`password.change.edit/update`) رمز فعلی را هم می‌خواهد
(اعتبارسنج `current_password` فریم‌ورک).

## ۳. بازیابی رمز

گردش استاندارد بروکر Laravel با اعلان فارسی (`ResetPasswordNotification`):

- `POST /forgot-password` با `throttle:5,1`؛ ایمیل ناشناس **همان پاسخ موفق** را می‌گیرد (Audit ‏`password_reset_unknown`، بدون ارسال ایمیل)؛
- throttle داخلی بروکر (۶۰ ثانیه بین دو درخواست یک ایمیل) به Audit ‏`password_reset_throttled` نگاشت می‌شود؛
- توکن یک‌بارمصرف با انقضای ۶۰ دقیقه (پیش‌فرض فریم‌ورک)؛ پس از مصرف موفق، توکن‌های API باطل و قفل ورود پاک می‌شود (Audit ‏`password_reset`).

## ۴. نشست

| کنترل | مقدار |
|---|---|
| لغزشی (بی‌فعالیتی) | ۱۲۰ دقیقه (`SESSION_LIFETIME`) |
| مطلق (مستقل از فعالیت) | ۱۲ ساعت (`hrm.auth.absolute_timeout_hours`)، اجرا توسط `EnsureSessionFresh` در هر درخواست احرازهویت‌شده |
| کوکی | `HttpOnly` + `SameSite=Lax`؛ `Secure` از `SESSION_SECURE_COOKIE` (روی XAMPP با HTTP باید false، در production حتماً true) |
| خروج | `logout` در همهٔ حالت‌ها (دکمه، timeout، غیرفعال‌سازی): invalidate + چرخش CSRF + Audit |

کاربر غیرفعال‌شده (`is_active=false`) در **اولین درخواست بعدی** بیرون انداخته می‌شود
(`EnsureUserIsActive` → Audit ‏`session_revoked`)؛ نشست منقضی → Audit ‏`session_expired`.

## ۵. MFA (TOTP)

پیاده‌سازی: `spomky-labs/otphp` (TOTP، دورهٔ ۳۰ ثانیه، leeway ‏۲۹ ثانیه) +
QR به‌صورت SVG درون‌خطی (`chillerlan/php-qrcode` — بدون هیچ درخواست خارجی، سازگار با CSP).

| موضوع | پیاده‌سازی |
|---|---|
| الزام | نقش‌های `hrm.auth.mfa_required_roles` = ‏`super-admin`، ‏`hr-admin`، ‏`finance`؛ یا پرچم `mfa_enforced` کاربر؛ یا **ثبت‌نام داوطلبانه** (هرکس enroll کند از آن پس ملزم است) |
| ثبت‌نام | `mfa.setup` نمایش QR + کلید متنی → تأیید با یک کد (`mfa.setup.confirm`، دارای `throttle:5,1`) → نمایش **یک‌بارهٔ** ۸ کد بازیابی |
| چالش | بعد از رمز صحیح، تا تأیید کد، همهٔ مسیرهای محافظت‌شده به `mfa.challenge` برمی‌گردند (`RequireMfa`)؛ ۵ کد اشتباه متوالی = پایان نشست |
| کدها | ارقام فارسی/عربی پذیرفته می‌شوند (نرمال‌سازی ورودی)؛ کد بازیابی هش‌شده، یک‌بارمصرف و پس از مصرف سوزانده می‌شود |
| مدیریت | تولید مجدد کدها (`mfa.codes.regenerate`)؛ غیرفعال‌سازی فقط با رمز فعلی (`mfa.destroy`) |

رویدادهای Audit: ‏`mfa_enrolled` · ‏`mfa_setup_failed` · ‏`mfa_verified` · ‏`mfa_failed` ·
‏`mfa_recovery_used` · ‏`mfa_codes_regenerated` · ‏`mfa_disabled`.

## ۶. توکن‌های API

| موضوع | پیاده‌سازی |
|---|---|
| صدور | `POST /api/v1/tokens` با ایمیل + رمز (+ `mfa_code` اگر حساب MFA دارد) → `201` با `token` و `expires_at` |
| عمر | ۳۰ روز (`hrm.auth.api_token_ttl_days`)؛ Sanctum توکن منقضی را خودش رد می‌کند |
| مصرف | هدر `Authorization: Bearer …`؛ `GET /api/v1/me` پروفایل مختصر برمی‌گرداند |
| مدیریت | `GET /api/v1/tokens` (فهرست توکن‌های خودم) · `DELETE /api/v1/tokens/{id}` (فقط مال خودم، وگرنه 404) |
| خطا | بدون توکن/نامعتبر → **401 با JSON**، هرگز redirect (حتی بدون هدر Accept) |
| abilities | در M2 همهٔ توکن‌ها `['*']`؛ abilities دانه‌ریز هم‌زمان با scopeهای داده (M4+) می‌آیند |

استک API هم `is-active` را دارد: توکن کاربر غیرفعال‌شده 401 می‌گیرد.

## ۷. هش رمز

`config/hashing.php`: پیش‌فرض **argon2id** اگر `PASSWORD_ARGON2ID` موجود باشد
(۶۴MiB/‏۴pass/‏۱thread)، وگرنه bcrypt با ۱۲ راند (`BCRYPT_ROUNDS`).
محیط test برای سرعت از bcrypt سبک استفاده می‌کند (`HashingTest` این قرارداد را قفل می‌کند).
راستی‌آزمایی نهایی روی XAMPP هدف با مالک استقرار است (دادهٔ واقعی هرگز وارد تست نمی‌شود).

## ۸. پیکربندی (خلاصه)

`SESSION_LIFETIME` · `SESSION_SECURE_COOKIE` · `HASH_DRIVER` · `BCRYPT_ROUNDS` ·
`hrm.auth.*` ‏(threshold/durations/absolute_timeout/mfa_required_roles/totp_leeway/
recovery_codes/max_attempts/token_ttl) — مقادیر پیش‌فرض امن‌اند و `.env.example` مستندشان می‌کند.

## ۹. تست‌ها

`tests/Feature/Auth/*` ‏(۳۱ تست: ورود، throttle، قفل، ریست، StrongPassword، چرخهٔ کامل MFA)،
`tests/Feature/Api/ApiAuthTest` ‏(۶ تست)، `tests/Feature/Rbac/RbacTest` (نشست و timeout)،
`tests/Feature/Security/*` (ماتریس، هشینگ، هدرها). جزئیات: `TESTING.md` §۳.
