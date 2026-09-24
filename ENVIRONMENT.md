# ENVIRONMENT — محیط‌ها و نصب

**Milestone 1** · توسعه روی **XAMPP (ویندوز)** · استقرار بعدی روی هاست اشتراکی/VPS.

---

## ۱. پیش‌نیازها

| ابزار | حداقل نسخه | توضیح |
|---|---|---|
| PHP | **8.3** | الزام Laravel 13 (نه 8.2). بررسی: `php -v` |
| اکستنشن‌های PHP | — | `mbstring pdo_mysql openssl tokenizer xml ctype json filter bcmath curl zip` (پیش‌فرض XAMPP کافی است) |
| MySQL / MariaDB | 8.0 / 10.6+ | نسخهٔ همراه XAMPP کافی است |
| Composer | 2.x | `composer -V` |
| Node.js | 20+ | فقط برای توسعه/بیلد فرانت‌اند (نه production) |

## ۲. نصب روی XAMPP (ویندوز) — گام‌به‌گام

### گام ۱ — آماده‌سازی XAMPP

1. XAMPP با **PHP 8.3 یا بالاتر** نصب کنید (در XAMPP Control Panel نسخه را ببینید).
2. از Control Panel، **Apache** و **MySQL** را Start کنید.
3. در phpMyAdmin (`http://localhost/phpmyadmin`) یک دیتابیس بسازید:

```sql
CREATE DATABASE hrm CHARACTER SET utf8mb4 COLLATE utf8mb4_persian_ci;
```

### گام ۲ — دریافت کد

```powershell
cd C:\xampp\htdocs
git clone https://github.com/ghatehresan/HRM.git hrm
cd hrm
```

### گام ۳ — وابستگی‌ها و پیکربندی

```powershell
composer install
copy .env.example .env
php artisan key:generate
php artisan migrate
npm install
npm run build
```

مقادیر `.env` برای XAMPP (پیش‌فرض `.env.example` همین‌هاست):

```
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=hrm
DB_USERNAME=root
DB_PASSWORD=
```

### گام ۴ — اجرا (یکی از دو روش)

**روش A — پیشنهادی برای توسعه (بدون تنظیم Apache):**

```powershell
php artisan serve
# → http://localhost:8000
```

**روش B — با Apache (شبیه‌سازی production):**

DocumentRoot باید دقیقاً روی `public/` باشد. در
`C:\xampp\apache\conf\extra\httpd-vhosts.conf`:

```apache
<VirtualHost *:80>
    DocumentRoot "C:/xampp/htdocs/hrm/public"
    ServerName hrm.local
    <Directory "C:/xampp/htdocs/hrm/public">
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>
```

و در `C:\Windows\System32\drivers\etc\hosts`:

```
127.0.0.1 hrm.local
```

سپس Apache را Restart کنید → `http://hrm.local`

> ### ⚠️ هشدار امنیتی (خواندن اجباری)
>
> اگر کد را مستقیم زیر `htdocs` بگذارید **بدون** تنظیم DocumentRoot،
> فایل `.env` (کلیدها و رمز DB) و پوشهٔ `storage/` (مدارک پرسنلی)
> با یک URL ساده قابل دسترسی خواهند بود.
>
> **راستی‌آزمایی:** بعد از راه‌اندازی، این آدرس باید 403/404 بدهد:
> `http://hrm.local/../.env` و `http://hrm.local/storage/...`
> (در روش B چون DocumentRoot روی `public/` است، ذاتاً امن است.)

### گام ۵ — راستی‌آزمایی نصب

```powershell
php artisan test          # همهٔ تست‌ها باید سبز باشند
vendor/bin/pint --test    # استایل کد
```

سپس در مرورگر: `/` (صفحهٔ خانه) و `/health` (وضعیت + اتصال DB).

## ۳. مرجع `.env`

| کلید | توسعه (XAMPP) | Production |
|---|---|---|
| `APP_ENV` | `local` | `production` |
| `APP_DEBUG` | `true` | **`false`** |
| `APP_URL` | `http://localhost:8000` | دامنهٔ واقعی با `https://` |
| `APP_KEY` | تولید با `key:generate` | یکتای تولید (هرگز کپی از dev) |
| `DB_*` | `hrm` / `root` / خالی | کاربر اختصاصی با کمترین دسترسی |
| `SESSION_DRIVER` | `database` | `database` |
| `QUEUE_CONNECTION` | `database` | `database` |
| `CACHE_STORE` | `database` | `database` |
| `MAIL_MAILER` | `log` | `smtp` (در M10 پیکربندی می‌شود) |
| `DISPLAY_TIMEZONE` | `Asia/Tehran` | `Asia/Tehran` |
| `LOG_LEVEL` | `debug` | `warning` |

> Redis/Memcached عمداً پشتیبانی نمی‌شوند (`AUDIT.md` §۲۲٫۵ X-1).

## ۴. Staging و Production (هاست اشتراکی/VPS)

جزئیات کامل استقرار در `DEPLOYMENT.md` (M12). اصول ثابت از همین امروز:

1. **ساختار پوشه:** فقط محتوای `public/` داخل `public_html`؛ بقیهٔ پروژه
   (شامل `.env` و `storage/`) **بیرون** از دسترس وب.
2. **دارایی‌ها:** `npm run build` روی ماشین توسعه/CI اجرا و خروجی
   `public/build` منتقل می‌شود — روی سرور به Node نیازی نیست.
3. **بدون SSH:** هر عملیات ضروری باید مسیر HTTP معادل داشته باشد
   (migration/seed اولیه از طریق اسکریپت وب محافظت‌شده — M12).
4. **زمان‌بند:** cron اگر بود؛ وگرنه مسیر HTTP با توکن + سرویس cron خارجی (M10).
5. **بکاپ:** خودکار، رمزنگاری‌شده، به مقصد خارج از سرور (M12).

## ۵. عیب‌یابی

| علامت | علت محتمل | راه‌حل |
|---|---|---|
| `SQLSTATE[HY000] [2002]` | MySQL در XAMPP خاموش است | Start کردن MySQL در Control Panel |
| `Access denied for user 'root'` | رمز root در XAMPP تنظیم شده | همان رمز در `DB_PASSWORD` |
| صفحهٔ سفید / خطای 500 | APP_KEY ساخته نشده | `php artisan key:generate` |
| استایل‌ها لود نمی‌شوند | بیلد فرانت‌اند انجام نشده | `npm install && npm run build` |
| `Vite manifest not found` | `public/build/manifest.json` نیست | `npm run build` |
| تست‌ها خطای DB می‌دهند | phpunit از sqlite حافظه استفاده می‌کند — اگر خطا داد، اکستنشن `pdo_sqlite` را در `php.ini` فعال کنید | `extension=pdo_sqlite` |
| ارور نسخهٔ PHP از Composer | PHP قدیمی در PATH | استفاده از `C:\xampp\php\php.exe` کامل یا به‌روزرسانی XAMPP |
| دسترسی مستقیم به `/.env` باز است | DocumentRoot اشتباه | روش B در گام ۴ (الزامی) |
