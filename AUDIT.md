# AUDIT — طراحی سیستم جامع منابع انسانی «قطعه‌رسان» (HRM)

**Milestone 0 — Repository Audit**
تاریخ: ۲ مهر ۱۴۰۵ (۲۴ سپتامبر ۲۰۲۶)
تهیه‌کننده: Senior Architecture Team
وضعیت: **منتظر تأیید معماری** — پیاده‌سازی گسترده پس از تأیید آغاز می‌شود.

---

## اصل حاکم بر این سند

هر آنچه در این سند آمده از **خواندن مستقیم Source Code** استخراج شده است.
هر موردی که از کد قابل استخراج نبوده با برچسب **`UNKNOWN`** علامت خورده و در بخش ۲۳ فهرست شده است.
**هیچ چیزی حدس زده نشده است.**

---

## ۰. خلاصهٔ اجرایی (Executive Summary)

پنج یافته که کل معماری را تعیین می‌کنند:

| # | یافته | پیامد معماری |
|---|---|---|
| ۱ | **هیچ Authenticationی در هیچ‌یک از سه سیستم وجود ندارد.** نه جدول `users`، نه `roles`، نه `permissions`، نه session/token واقعی. | HRM **اولین و تنها** دارای Identity خواهد بود. احراز هویت باید از صفر ساخته شود — قابل اشتراک‌گذاری نیست، چون چیزی برای اشتراک وجود ندارد. |
| ۲ | **Management System هیچ چیزی از HRM پیاده‌سازی نکرده است.** دامنهٔ آن تأمین/فروش/انبار قطعات یدکی خودرو است. | **چیزی برای «دوباره نساختن» وجود ندارد.** HRM یک دامنهٔ کاملاً جدید است؛ خطر Duplicate تقریباً صفر است (به‌جز مفاهیم Organization که آن‌ها هم وجود ندارند). |
| ۳ | **هیچ APIای وجود ندارد.** تنها خروجی‌ها: فایل CSV و دانلود پشتیبان. | HRM اولین سرویس API-first اکوسیستم خواهد بود و باید **قرارداد integration آینده** را تعریف کند. |
| ۴ | **Website واقعی (`ghatehresan-website`) یک قالب HTML استاتیک خریداری‌شده («آرینو») است** با رنگ‌ها و فونتی که با کتاب برند **تضاد دارد**. | Brand Repository مرجع الزامی است. Website نیازمند **بازنگری برند** دارد، نه اینکه به‌عنوان مرجع UI استفاده شود. |
| ۵ | Repository `website` که در مأموریت اشاره شده **عملاً خالی است** (یک فایل ۱۰ بایتی). | Website مأموریت = «هنوز ساخته نشده». معماری Careers باید برای آینده آماده شود، نه برای کد موجود. |

**نتیجه:** HRM یک پروژهٔ **Greenfield با محدودیت برند و محدودیت مالکیت داده** است — نه یک بازنویسی، نه یک الحاقیه.

---

## ۱. وضعیت دسترسی به Repositoryها

همهٔ Repositoryها **Public** و **کاملاً قابل دسترسی** هستند. هیچ‌کدام Private نیستند.

| Repository | وضعیت | دسترسی | تعداد Commit | حجم | آخرین Push |
|---|---|---|---|---|---|
| `ghatehresan/brand-ghatehresan` | 🟢 Public | ✅ کامل | ۱ | ۵٫۹ MB | ۱۴۰۵/۰۷/۰۱ |
| `ghatehresan/ghatehresan-management-system` | 🟢 Public | ✅ کامل | ۳ | ۱٫۱ MB | ۱۴۰۵/۰۷/۰۱ |
| `ghatehresan/website` | 🟢 Public | ✅ کامل | ۱ | ۰ KB | ۱۴۰۵/۰۷/۰۲ |
| `ghatehresan/ghatehresan-website` ⚠️ | 🟢 Public | ✅ کامل | ۱ | ۲۰ MB | ۱۴۰۵/۰۷/۰۱ |
| `ghatehresan/HRM` (این Repository) | 🟢 Public | ✅ کامل | ۱ | ۰ KB | ۱۴۰۵/۰۷/۰۲ |

### ⚠️ کشف خارج از مأموریت

علاوه بر `website` (خالی)، یک Repository چهارم به نام **`ghatehresan-website`** وجود دارد که **Website واقعی پروژه است** و در مأموریت ذکر نشده بود. این Repository حاوی ۱۰۰+ صفحهٔ HTML است و در این Audit بررسی شده است.

**بررسی Repository از‌پیش‌موجود HRM:**
- `ghatehresan/ghatehresan-hrm` → ❌ **وجود ندارد** (تأیید‌شده با API)
- `ghatehresan/HRM` → ✅ وجود دارد و Repository فعلی ماست

**توصیه:** نام فعلی `HRM` حفظ شود (ساختن `ghatehresan-hrm` ایجاد سردرگمی می‌کند).

---

## ۲. معماری موجود (Existing Architecture)

### ۲.۱ نقشهٔ واقعی اکوسیستم (بر اساس کد، نه فرض)

```
                    ┌────────────────────────────────┐
                    │  brand-ghatehresan  (Public)   │
                    │  ─────────────────────────────  │
                    │  brand-book.html   (هویت بصری) │
                    │  strategy.html     (استراتژی)  │
                    │  logo/*.svg, *.png (دارایی)    │
                    │  رنگ-های-برند.txt  (tokens)    │
                    │  ⚠️ هیچ کد / package / token   │
                    │     file ماشین‌خوان ندارد       │
                    └───────────────┬────────────────┘
                                    │  کپی دستی (Manual Copy)
                        ┌───────────┴───────────┐
                        ▼                       ▼
        ┌───────────────────────────┐   ┌──────────────────────────┐
        │ ghatehresan-management-   │   │ ghatehresan-website      │
        │ system                    │   │ (قالب استاتیک «آرینو»)   │
        │ ─────────────────────────  │   │ ──────────────────────── │
        │ PHP 7.4+ بدون Framework   │   │ HTML استاتیک + Tailwind 4│
        │ PDO + SQLite (یا MySQL)   │   │ ۱۰۰+ صفحهٔ Mockup        │
        │ Server-Rendered HTML      │   │ login / register (فیک)   │
        │ ۱۱ جدول — دامنه قطعات     │   │ user/seller/admin panel  │
        │ ❌ بدون Auth              │   │ ❌ بدون Backend          │
        │ ❌ بدون API               │   │ ❌ بدون Database         │
        │ ❌ بدون File Upload       │   │ ⚠️ رنگ/فونت مغایر برند   │
        └───────────────────────────┘   └──────────────────────────┘
                        │                       │
                        └───────────┬───────────┘
                                    │
                     ❌ هیچ کانال ارتباطی وجود ندارد
                     ❌ هیچ Identity مشترکی وجود ندارد
                     ❌ هیچ API مشترکی وجود ندارد
                     ❌ هیچ Package/Submodule مشترکی وجود ندارد

        ┌───────────────────────────────────────────┐
        │ website  →  فقط "# website" (۱۰ بایت)     │
        │ HRM      →  فقط "# HRM"    (۶ بایت)       │
        └───────────────────────────────────────────┘
```

### ۲.۲ واقعیت حیاتی

**هیچ «Shared Service»، «Shared Authentication» یا «Shared API» ای در کار نیست.**
دیاگرام پیشنهادی مأموریت (با لایهٔ Shared Services) یک **هدف** است، نه وضعیت موجود.
HRM باید این لایه را **ایجاد** کند — برای نخستین‌بار.

---

## ۳. Technology Stack موجود

### ۳.۱ Management System

| لایه | فناوری | شاهد از کد |
|---|---|---|
| زبان | PHP 7.4+ | `public/index.php:39` — `PHP_VERSION_ID < 70400` |
| Framework | **ندارد** (Vanilla PHP) | ساختار `app/pages/*.php` دستی |
| الگوی معماری | Front Controller + Page Script | `public/index.php` مسیریابی با `?p=` |
| DB Access | PDO با Prepared Statements | `app/db.php:65` — `q()` |
| Database | **SQLite** (پیش‌فرض) یا MySQL | `app/config.php:19` — `'driver' => 'sqlite'` |
| ORM | **ندارد** — SQL خام | `app/db.php` |
| Migration | **ندارد** — `CREATE TABLE IF NOT EXISTS` در هر request | `app/db.php:245` — داخل `migrate()` که هر بار صدا زده می‌شود |
| Templating | PHP خالص در HTML | `app/layout.php`, `app/pages/*.php` |
| CSS | CSS دستی (۳۹۴ خط) با Custom Properties | `public/assets/app.css` |
| JS | Vanilla JS (۲۱۴ خط)، بدون Framework | `public/assets/app.js` |
| Session | PHP Session — **فقط برای CSRF و Flash** | `public/index.php:27` |
| API | **ندارد** | فقط `export.php` (CSV) |
| File Upload | **ندارد** | `grep -i "\$_FILES"` → خالی |
| Notification | **ندارد** | — |
| Audit Log | **ندارد** | — |
| Background Job | **ندارد** | — |
| Test | **ندارد** | — |
| Build Tool | **ندارد** | — |
| Package Manager | **ندارد** (بدون composer.json) | — |

**حجم کل کد:** ۵٬۲۳۶ خط (PHP + CSS + JS)

### ۳.۲ Website (`ghatehresan-website`)

| لایه | فناوری | شاهد از کد |
|---|---|---|
| نوع | **قالب HTML استاتیک خریداری‌شده** | `login.html:9` — `قالب فرشگاهی آرینو` |
| CSS | Tailwind CSS v4 (CLI) | `package.json` — `@tailwindcss/cli ^4.0.0` |
| فونت فارسی | **Peyda (پیدا)** | `src/input.css` — `@font-face { font-family: "payda" }` |
| فونت لاتین | Inter | `src/input.css` |
| JS | Vanilla + Swiper + Chart.js + Prism | `public/assets/js/` |
| Backend | **ندارد** | تمام `<form action="">` یا `action="/newsletter"` |
| Database | **ندارد** | — |
| Auth | **فیک / Mock سمت کلاینت** | `login.html` — اعتبارسنجی `min ۶ کاراکتر` و OTP ساختگی |

### ۳.۳ Brand Repository

| مورد | مقدار |
|---|---|
| نوع محتوا | اسناد HTML + دارایی گرافیکی + یک فایل اکسل |
| کد | **ندارد** |
| فایل ماشین‌خوان tokens | **ندارد** (`رنگ-های-برند.txt` متن ساده است) |
| بسته‌بندی | یک فایل ZIP در ریشه (۴۶ فایل داخل آن) |

### ۳.۴ نتیجه‌گیری Stack

هیچ Stack مشترکی وجود ندارد. تنها اشتراک واقعی: **PHP در سمت Management System** و **Tailwind v4 در سمت Website**.

---

## ۴. تحلیل برند (Brand Analysis)

**منبع:** `brand-ghatehresan/brand-ghatehresan.zip` → `brand-book.html`، `strategy.html`، `رنگ-های-برند.txt`، `logo/`

### ۴.۱ Design Tokens — استخراج‌شده از `:root` در `brand-book.html`

این مقادیر **واقعی** هستند و از کد استخراج شده‌اند (کاملاً با Management System یکسان‌اند):

```css
:root {
  /* رنگ‌های اصلی */
  --navy:    #0E2A47;  /* سرمه‌ای فولادی — Steel Navy — رنگ غالب */
  --orange:  #F26B1D;  /* نارنجی رِله — Relay Orange — فقط نقطهٔ عمل */
  --oil:     #111820;  /* مشکی روغنی — متن اصلی */
  --steel:   #55616E;  /* خاکستری فولاد — متن فرعی */

  /* خنثی */
  --line:    #E8EBEF;  /* خط و جداکننده */
  --paper:   #F9FAFB;  /* سفید کارگاه — پس‌زمینه */

  /* رنگ‌های کارکردی */
  --green:   #1F9D55;  /* موجود / موفق */
  --red:     #D93025;  /* ناموجود / خطا */
  --amber:   #F0A202;  /* هشدار */
  --info:    #2563EB;  /* اطلاع‌رسانی */
}
```

**RGB / CMYK (از `رنگ-های-برند.txt`):**

| نقش | نام فارسی | HEX | RGB | CMYK |
|---|---|---|---|---|
| اصلی | سرمه‌ای فولادی | `#0E2A47` | 14, 42, 71 | 80 / 41 / 0 / 72 |
| تأکید | نارنجی رِله | `#F26B1D` | 242, 107, 29 | 0 / 56 / 88 / 5 |
| متن اصلی | مشکی روغنی | `#111820` | 17, 24, 32 | — |
| متن فرعی | خاکستری فولاد | `#55616E` | 85, 97, 110 | — |
| خط | خاکستری روشن | `#E8EBEF` | 232, 235, 239 | — |
| پس‌زمینه | سفید کارگاه | `#F9FAFB` | 249, 250, 251 | — |

### ۴.۲ قواعد سخت‌گیرانهٔ برند (الزامی برای HRM)

| قاعده | مقدار | منبع |
|---|---|---|
| **قاعدهٔ ۶۰/۳۰/۱۰** | ۶۰٪ خنثی · ۳۰٪ سرمه‌ای · ۱۰٪ نارنجی | brand-book §۰۵ |
| **سقف نارنجی** | **حداکثر ۱۰٪ سطح صفحه** — «وگرنه قدرت فراخوان به عمل را از دست می‌دهد» | brand-book §۰۵ |
| **نارنجی رنگ متن نیست** | متن ریز نارنجی روی سفید **ممنوع** است | brand-book §۰۵ |
| کنتراست سرمه‌ای روی سفید | **14.6:1 — AAA** | brand-book §۰۵ |
| کنتراست نارنجی روی سرمه‌ای | **4.8:1 — AA** (متن معمولی مجاز) | brand-book §۰۵ |
| کنتراست نارنجی روی سفید | **3.0:1 — محدود** (فقط تیتر ≥24px و آیکون) | brand-book §۰۵ |
| کنتراست سفید روی نارنجی | **3.0:1 — محدود** (فقط دکمه با متن درشت بولد) | brand-book §۰۵ |

> این جدول کنتراست مستقیماً **الزامات Accessibility** (بخش ۴۴ مأموریت) را تأمین می‌کند و نیازی به حدس ندارد.

### ۴.۳ تایپوگرافی

**فونت:** وزیرمتن (**Vazirmatn**) — وزن‌های ۴۰۰ / ۵۰۰ / ۷۰۰ / ۹۰۰
**فونت مکمل لاتین/اعداد فنی:** **Inter**
**الزام:** «روی سرور خودتان میزبانی کنید (**نه CDN خارجی**) تا سرعت بارگذاری در ایران بهتر باشد.»

**مقیاس نوعی (از brand-book §۰۶):**

| سطح | اندازه | وزن | ارتفاع سطر | کاربرد |
|---|---|---|---|---|
| نمایشی | 48–60px | 900 | 1.35 | تیتر صفحهٔ اصلی، بنر |
| تیتر ۱ | 34px | 900 | 1.4 | عنوان صفحه |
| تیتر ۲ | 24px | 700 | 1.5 | عنوان بخش |
| تیتر ۳ | 19px | 700 | 1.6 | نام موجودیت |
| بدنه | 16.5px | 400 | **1.9** | متن اصلی |
| کوچک | 14px | 400 | 1.8 | توضیح، پانویس |
| برچسب/دکمه | 15px | 500 | 1.2 | دکمه، تگ، منو |

**قواعد نوشتاری:**
- ✅ ارتفاع سطر فارسی حداقل **۱٫۸** برای بدنه
- ✅ اعداد قیمت و تاریخ: **فارسی** (۱٬۲۸۳٬۴۸۶)
- ✅ شماره فنی و کد: همیشه **لاتین** (`EMP-0142`)
- ✅ نیم‌فاصله در «قطعه‌رسان»، «می‌رسونیم»
- ❌ بیش از دو فونت در یک صفحه
- ❌ متن فارسی با ارتفاع سطر کمتر از ۱٫۶

### ۴.۴ نشان (Logo)

نماد = **مهرهٔ شش‌گوش** (قطعه) + **فلش خروجی** (رسان)

| دارایی | فایل | کاربرد |
|---|---|---|
| اصلی عمودی | `logo/logo-primary.svg` (400×400) | حالت پیش‌فرض |
| افقی | `logo/logo-horizontal.svg` (740×240) | هدر سایت، سربرگ |
| آیکون | `logo/logo-icon.svg` (240×240) | اپلیکیشن، فاوآیکون |
| تک‌رنگ | `logo/logo-mono.svg` | مهر، حکاکی؛ رنگ با `currentColor` |

**محدودیت‌های سخت:**
- حریم امن = **نصف ارتفاع مهرهٔ شش‌گوش**
- حداقل عرض دیجیتال لوگوی کامل = **120px**
- حداقل عرض فقط نماد = **32px**
- ❌ تغییر نسبت · ❌ تغییر رنگ/گرادیان · ❌ چرخاندن · ❌ سایه/افکت · ❌ بازنویسی نام‌نویس

> ⚠️ **نکتهٔ فنی:** فایل‌های SVG از `@import` گوگل‌فونت برای Vazirmatn استفاده می‌کنند
> (`<style>@import url('https://fonts.googleapis.com/css2?family=Vazirmatn...')</style>`)
> که با الزام خود برند («نه CDN خارجی») **تضاد دارد** و در ایران احتمالاً مسدود است.
> **اقدام اصلاحی در Milestone 1:** حذف `@import` و تبدیل `<text>` به outline، یا بارگذاری فونت محلی.

### ۴.۵ زبان بصری

| عنصر | مشخصات |
|---|---|
| بافت شش‌گوش | پس‌زمینهٔ هدر/بنر با شفافیت **۱۵ تا ۲۵٪** |
| موتیف فلش | جداکنندهٔ بخش‌ها، نشانگر مسیر |
| برچسب وضعیت | گوشهٔ گرد **5px**، متن **12px بولد**، پس‌زمینهٔ کم‌رنگ |
| سبک آیکون | **خطی (outline)**، ضخامت **۲ پیکسل**، گوشهٔ گرد، شبکهٔ **۲۴×۲۴**، پیش‌فرض سرمه‌ای؛ نارنجی فقط برای آیکون فعال |

### ۴.۶ لحن و صدا (Tone of Voice)

**شعار اصلی:** «قطعه‌ات رو می‌رسونیم»
**شخصیت:** «خودمانی اما دقیق. مثل کسی که کارش را بلد است.»

| ✅ بنویس | ❌ ننویس |
|---|---|
| «۴ عدد موجوده» | «موجود» |
| «الان نداریم، ۳ روز دیگه می‌رسه» | «برای قیمت تماس بگیرید» |
| محاوره‌ای اما مؤدب: «برات می‌فرستیم» | لحن اداری خشک: «بدینوسیله به استحضار می‌رساند…» |
| اعداد دقیق و واقعی | «بهترین در ایران»، «بی‌نظیرترین» |

### ۴.۷ معماری برند (Brand Architecture)

کتاب برند ساختار **Master-brand** را تعیین کرده است:

```
قطعه‌رسان              → فروشگاه و برند مادر
قطعه‌رسان بیزینس       → پنل عمده و B2B
قطعه‌رسان اکسپرس       → ارسال سریع درون‌شهری
باشگاه قطعه‌رسان       → وفاداری تعمیرکاران
```

**نتیجه برای HRM:** HRM یک برند مستقل نیست. نام پیشنهادی:
**«سامانهٔ منابع انسانی قطعه‌رسان»** / **قطعه‌رسان — منابع انسانی**
زیرِ برند مادر، با همان هویت بصری.

### ۴.۸ ⚠️ تضاد برند در Website موجود

| مورد | کتاب برند (مرجع) | Website موجود | وضعیت |
|---|---|---|---|
| رنگ اصلی | `#0E2A47` سرمه‌ای | `#272C48` | ❌ مغایر |
| رنگ تأکید | `#F26B1D` نارنجی رِله | `#FF8229` نارنجی متفاوت | ❌ مغایر |
| موفقیت | `#1F9D55` | `#10B981` | ❌ مغایر |
| خطا | `#D93025` | `#EF4444` | ❌ مغایر |
| هشدار | `#F0A202` | `#F59E0B` | ❌ مغایر |
| فونت فارسی | **Vazirmatn** | **Peyda** | ❌ مغایر |

> **الزام:** HRM از این مقادیر Website **الگوبرداری نخواهد کرد**.
> Brand Repository مرجع انحصاری است (طبق مأموریت: «هیچ Design Language جدیدی که با Brand Repository تضاد دارد ایجاد نکن»).

---

## ۵. تحلیل Management System

**منبع:** `ghatehresan-management-system/ghatehresan-management-system.zip`

### ۵.۱ ساختار فایل

```
ghatehresan-management system/
├── index.php                  → ریدایرکت به public/
├── .gitignore                 (۳ خط)
├── app/
│   ├── config.php             (۵۵ خط)  تنظیمات، درایور DB
│   ├── db.php                 (۴۲۸ خط) PDO، migrate()، seed_defaults()، محاسبات مالی
│   ├── helpers.php            (۲۴۵ خط) e()، Jalali، CSRF، badge()، money()
│   ├── layout.php             (۱۰۸ خط) Sidebar، nav، page_head()، empty_box()
│   ├── demo.php               (۱۸۱ خط) دادهٔ نمونه
│   └── pages/                 (۱۶ صفحه)
├── public/
│   ├── index.php              (۵۹ خط)  نقطهٔ ورود + مسیریابی
│   └── assets/
│       ├── app.css            (۳۹۴ خط) Design System واقعی پروژه
│       ├── app.js             (۲۱۴ خط)
│       ├── favicon.svg
│       └── fonts/Vazirmatn-{Regular,Medium,Bold,Black}.woff2
└── storage/
    ├── .htaccess              → Require all denied
    ├── ghatehresan.sqlite
    └── index.html
```

### ۵.۲ مسیریابی (Routing)

```php
$pages = ['dashboard','products','product_edit','orders','order_edit','order_view',
          'missed','stock','decision','suppliers','supplier_edit',
          'customers','customer_edit','reports','settings','export'];
$p = $_GET['p'] ?? 'dashboard';
if (!in_array($p, $pages, true)) $p = 'dashboard';
```

**۱۶ صفحه. همه در یک فضای تخت (flat namespace)، بدون گروه‌بندی ماژول.**

### ۵.۳ منوی سایدبار (ناوبری موجود)

| کلید | برچسب فارسی | دامنه |
|---|---|---|
| `dashboard` | داشبورد | — |
| `products` | کاتالوگ | قطعات |
| `orders` | سفارش‌ها | فروش |
| `missed` | دفتر نداشتیم | تقاضای برآورده‌نشده |
| `stock` | انبار | موجودی |
| `decision` | تصمیم انبار | تحلیل |
| `suppliers` | تأمین‌کننده | تأمین |
| `customers` | مشتریان | مشتری |
| `reports` | گزارش‌ها | گزارش |
| `settings` | تنظیمات | پیکربندی |

**هیچ آیتمی مرتبط با HR، پرسنل، سازمان یا کاربر در ناوبری وجود ندارد.**

### ۵.۴ Design System موجود (استخراج‌شده از `app.css`)

این فهرستِ **کامل** کلاس‌های موجود است — مبنای بازطراحی برای HRM:

```
ساختار:      .side .main .brand .side-ft .mobile-bar .phead .pactions
دکمه:        .btn .btn-p (نارنجی) .btn-n (سرمه‌ای) .btn-d (قرمز) .btn-sm
کارت:        .card .card-h .card-b
جدول:        .tw (wrapper) .tight .wide .num .tl .nowrap .r .r2 .r3 .r4
وضعیت:       .badge .b-green .b-red .b-amber .b-info .b-navy .b-orange .b-gray
آمار:        .stats .g2 .g3 .g4 .stat .lb .vl .sm .pos .neg .acc .muted
بازخورد:     .flash .f-green .f-red .f-amber .note .n-green .n-red .n-amber .n-info
فرم:         .frm .fld .row .inline-add .filters .chips .chip .chk .chks .hint .opt
حالت خالی:   .empty .ei
پیجینیشن:    .pager
پیشرفت:      .pbar .pbar-f .pbar-t .pbar-r .bars .bar .bar-col .bar-lb .bar-wrap .bar-net
تب:          .tabs
سایر:        .kv .dv .legend .dots .cur .sw .sw-navy .sw-orange .money-in .en .mono
```

**توکن‌های `app.css` (یکسان با برند):**
```css
:root{
  --navy:#0E2A47; --navy-2:#16375A; --orange:#F26B1D; --orange-d:#D25510;
  --ink:#111820; --steel:#55616E; --line:#E8EBEF; --paper:#F9FAFB;
  --green:#1F9D55; --red:#D93025; --amber:#F0A202; --info:#2563EB;
  --side:252px; --radius:12px;
  --shadow:0 1px 3px rgba(14,42,71,.07), 0 4px 14px rgba(14,42,71,.05);
}
```

**رنگ‌های مشتق‌شده (تیره‌تر برای hover):**
```
--navy-2 (hover سرمه‌ای):  #16375A
--orange-d (hover نارنجی): #D25510
قرمز hover:                #a3201a
سبز hover:                 #15703c
آبی hover:                 #1a47a8
کهربا متن:                 #7a5200
مرز hover عمومی:           #c9d2dc
متن سایدبار:               #c6d3e1
متن کم‌رنگ سایدبار:        #8aa0b8 / #6b7f96
```

**رفتارهای تعاملی:**
- سایدبار **RTL**: `position:fixed; top:0; right:0; width:252px`
- محتوا: `margin-right: var(--side)` (یعنی **margin-inline-start** در RTL)
- آیتم فعال سایدبار: `background: var(--orange)` + وزن ۷۰۰
- فونت بدنه: **14.5px / line-height 1.85** (نزدیک به ۱۶٫۵/۱٫۹ برند اما کوچکتر — مناسب ابزار داخلی)
- Breakpoint: **`@media (max-width:900px)`** → سایدبار تبدیل به drawer با `.open`

### ۵.۵ مدیریت تاریخ و تقویم (بسیار مهم برای HRM)

**روش فعلی — استخراج‌شده از `helpers.php`:**

```php
// ذخیره‌سازی: TEXT با فرمت Y-m-d (میلادی)
$NOW = "(datetime('now','localtime'))";   // ← db.php:91

// تبدیل به شمسی در لایهٔ نمایش
function jdate($ymd, $format = 'short') { ... }
function gregorian_to_jalali($gy,$gm,$gd) { ... }
function jalali_to_gregorian($jy,$jm,$jd) { ... }
function jtoday() { ... }
function jalali_str_to_ymd($s) { ... }
function jmonth_range($jy,$jm) { ... }

const JMONTHS = ['','فروردین','اردیبهشت','خرداد','تیر','مرداد','شهریور',
                 'مهر','آبان','آذر','دی','بهمن','اسفند'];
```

| جنبه | وضعیت موجود | ارزیابی |
|---|---|---|
| فرمت ذخیره | `TEXT` با `Y-m-d` میلادی | ⚠️ رشته به‌جای نوع DATE |
| منطقهٔ زمانی | `date_default_timezone_set('Asia/Tehran')` | ⚠️ |
| زمان تولید | `datetime('now','localtime')` | ❌ **نقص:** زمان محلی naive ذخیره می‌شود، **نه UTC** |
| نمایش | تبدیل به Jalali در Presentation | ✅ صحیح |
| دقت الگوریتم | الگوریتم ۳۳سالهٔ استاندارد | ✅ برای ۱۳۰۰–۱۵۰۰ شمسی قابل قبول |
| بازهٔ ماه شمسی | `jmonth_range()` — هر دو سر **شامل** | ✅ دقیق و مستند |

> **الزام برای HRM:** هرگز `localtime` را کپی نکن.
> ذخیره: **UTC**. نمایش: **Jalali + Asia/Tehran**. تاریخ‌های مدنی (تولد، استخدام) بدون منطقهٔ زمانی.

### ۵.۶ مدیریت اعداد فارسی

```php
function fa_digits($s)  { /* 0-9 → ۰-۹ */ }
function en_digits($s)  { /* ۰-۹ و ٠-٩ → 0-9 */ }
function money($n, $fa = true) { /* number_format با ٬ و ارقام فارسی */ }
function to_int($v)     { en_digits + strip غیرعدد }
```
در سمت کلاینت (`app.js`): تبدیل خودکار ارقام فارسی/عربی به لاتین در `input.money`.

> **الزام برای HRM:** همین الگو — ورودی‌ها به لاتین نرمال شوند، خروجی‌ها با ارقام فارسی نمایش داده شوند. طبق برند: **مبالغ و تاریخ‌ها فارسی؛ کدها و شماره فنی لاتین.**

### ۵.۷ ✅ پاسخ به سؤال کلیدی مأموریت

> **آیا Management System در حال حاضر چیزی از HRM را پیاده‌سازی کرده است؟**

# ❌ خیر. هیچ.

شواهد قطعی:

| مفهوم HRM | جست‌وجو در کد | نتیجه |
|---|---|---|
| User / کاربر | `grep -i "user\|کاربر"` | ❌ فقط پیام «اگر کاربر به‌جای پوشهٔ public آمد» |
| Employee / کارمند / پرسنل | `grep -i "employee\|کارمند\|پرسنل"` | ❌ هیچ |
| Role / نقش | `grep -i "role"` | ⚠️ فقط `suppliers.role` = «نقش تأمین‌کننده در کسب‌وکار» |
| Permission / دسترسی | `grep -i "permission\|دسترسی"` | ❌ هیچ |
| Department / واحد | `grep -i "department\|واحد"` | ❌ هیچ |
| Password / Login | `grep -i "password\|login"` | ❌ فقط `config.php` رمز MySQL |
| حقوق / دستمزد | `grep -i "حقوق\|salary"` | ❌ هیچ |
| مرخصی / حضورغیاب | `grep -i "مرخصی\|leave\|attendance"` | ❌ هیچ |

**نتیجهٔ قطعی:**

| تصمیم | پاسخ |
|---|---|
| آیا باید چیزی **Reuse** شود؟ | **بله** — اما فقط در لایهٔ **بصری و ابزارها**: Design tokens، الگوی سایدبار/کارت/جدول، توابع Jalali و ارقام فارسی، توابع `e()` و CSRF |
| آیا باید چیزی **Extend** شود؟ | **خیر** — دامنه‌ها کاملاً مجزا هستند |
| آیا باید چیزی **Extract** شود؟ | **بله** — توابع Jalali/Persian و Design tokens باید به یک **بستهٔ مشترک** منتقل شوند (Milestone 1) |
| آیا باید چیزی به HRM **منتقل** شود؟ | **خیر** — Management System چیزی از HR ندارد |

---

## ۶. تحلیل Website

دو Repository باید از هم تفکیک شوند:

### ۶.۱ `ghatehresan/website` — اشاره‌شده در مأموریت

```
website/
└── README.md      (۱۰ بایت)
```
محتوا: `# website`
تعداد Commit: **۱** — ایجاد توسط `arena-ai-coding-agent[bot]` در ۱۴۰۵/۰۷/۰۲

**ارزیابی:** عملاً **خالی**. چیزی برای Audit ندارد.

### ۶.۲ `ghatehresan/ghatehresan-website` — Website واقعی

| جنبه | یافته |
|---|---|
| نوع | قالب HTML استاتیک خریداری‌شده به‌نام **«آرینو»** (مشابه دیجی‌کالا) |
| تعداد صفحه | **۱۰۰+** فایل HTML |
| Backend | **ندارد** |
| Database | **ندارد** |
| Authentication | **ندارد (فیک سمت کلاینت)** |
| API | **ندارد** |

**ساختار صفحات:**
```
فروشگاه:    index, shop, product, cart, cart-empty, checkout, compare, search,
            payment, success-payment, fail-payment, blog, blog-single, faq,
            contact-us, about-us, terms-and-rules, privacy-policy,
            return-procedure, page-404, landing, skeleton-index, blank
احراز هویت: login, register, forgot-password
پنل کاربر:  user-panel-*  (۲۵ صفحه)
پنل فروشنده: seller-panel-* (۳۰ صفحه)
پنل مدیر:   admin-panel-*  (۲۲ صفحه)
```

**احراز هویت — بررسی دقیق (`login.html`):**
```html
<form class="space-y-5" id="password-form">          <!-- action ندارد -->
  <input type="text"     id="username" placeholder="نام کاربری یا شماره موبایل" required>
  <input type="password" id="password" placeholder="رمز عبور خود را وارد کنید" required>
  <p class="error-message hidden" id="password-error">رمز عبور باید حداقل ۶ کاراکتر باشد</p>

<!-- تب دوم: ورود با پیامک -->
<form class="space-y-5 hidden" id="sms-form">
  <input type="tel"  id="mobile"   placeholder="09xxxxxxxxx" required>
  <input type="text" id="otp-code" placeholder="کد ۵ رقمی ارسال شده" required>
```

- `<form>` بدون `action` و بدون `method` → **هیچ ارسالی به سرور انجام نمی‌شود**
- هیچ `addEventListener('submit')` یافت نشد
- OTP یک **Mock خالص** است
- هیچ `fetch()` به endpoint احراز هویت وجود ندارد

**ساختار User / Role / Log در Admin Panel (فقط Mockup، بدون persistence):**

`admin-panel-users-list.html` — ستون‌های جدول:
```
# · نام کاربر · ایمیل · نقش · وضعیت · عملیات
```

`admin-panel-user-roles.html` — ستون‌های جدول:
```
# · نام نقش · شناسه · تعداد کاربران · سطح دسترسی · عملیات
```
نقش‌های نمونه:
| نام | شناسه | توضیح | تعداد کاربر | سطح |
|---|---|---|---|---|
| مدیر سیستم | `admin` | دسترسی کامل به تمام بخش‌ها | ۳ | کامل |
| نویسنده | `author` | مدیریت محتوا و مقالات | ۵ | متوسط |
| کاربر عادی | `user` | دسترسی محدود به پروفایل کاربری | ۴۲ | محدود |

`admin-panel-add-user.html` — فیلدها:
```
نام کامل · نام کاربری · آدرس ایمیل · شماره تلفن ·
رمز عبور · تکرار رمز عبور · نقش کاربر · وضعیت حساب (فعال/غیرفعال) · تصویر پروفایل
```

`admin-panel-logs.html` — ستون‌های جدول:
```
تاریخ و زمان · نوع · کاربر · IP · پیام · جزئیات
```

> این آخری عملاً همان چیزی است که مأموریت برای **MODULE 20 — Audit Log** می‌خواهد:
> `Who · What · When · Where(IP) · Before/After`. می‌تواند به‌عنوان **مرجع طراحی UI** استفاده شود (نه پیاده‌سازی).

**منوی Admin Panel موجود:**
```
پیشخوان
مدیریت کاربران      → لیست کاربران · افزودن کاربر · نقش‌های کاربری
مدیریت محصولات      → لیست · افزودن · دسته‌بندی‌ها · ویژگی‌ها · نظرات
مدیریت سفارشات      → لیست · جزئیات · وضعیت · تخفیف‌ها و کوپن‌ها · تراکنش‌ها
گزارشات             → فروش · مشتریان · محصولات
تنظیمات سیستم       → پشتیبان‌گیری · لاگ‌های سیستم
```

**⚠️ ریسک برند در Website:**
تصاویر حاوی لوگوها و نشان‌های قالب هستند:
```
images/logo/digikala.webp · digipay.svg · google.jpg · telegram.png
images/namad/enamad.png · namad-01.png · rezi.png
images/bank-logo/zarinpal... · next-pay...
```
این‌ها **دارایی‌های قالب** هستند و برای یک برند واقعی **ریسک حقوقی و اعتباری** دارند.

### ۶.۳ پاسخ به سؤالات مأموریت دربارهٔ Website

| سؤال | پاسخ (مستند به کد) |
|---|---|
| آیا Login وجود دارد؟ | ⚠️ **ظاهر** دارد، **عملکرد** ندارد. Mock سمت کلاینت. |
| آیا User system وجود دارد؟ | ❌ خیر. هیچ persistence. |
| آیا Customer account وجود دارد؟ | ❌ خیر. فقط ۲۵ صفحهٔ Mockup `user-panel-*`. |
| API چیست؟ | ❌ **هیچ**. تنها `<form action="/newsletter">`. |
| Authentication چگونه انجام می‌شود؟ | ❌ **انجام نمی‌شود**. |
| آیا Employee-facing functionality وجود دارد؟ | ❌ خیر. |
| آیا صفحهٔ Careers / استخدام وجود دارد؟ | ❌ خیر (جست‌وجو برای «استخدام\|careers\|فرصت شغلی» → هیچ). |

---

## ۷. Authentication موجود

# ❌ در هیچ‌یک از سیستم‌ها وجود ندارد.

### ۷.۱ شواهد

| سیستم | User Model | Token | Session | OAuth/SSO | MFA |
|---|---|---|---|---|---|
| Management System | ❌ | ❌ | ⚠️ فقط CSRF/Flash | ❌ | ❌ |
| `ghatehresan-website` | ❌ (Mockup) | ❌ | ❌ | ❌ | ⚠️ فقط UI ساختگی OTP |
| `website` | ❌ | ❌ | ❌ | ❌ | ❌ |

### ۷.۲ آنچه در Management System **هست** (و آنچه نیست)

```php
// public/index.php:27
if (session_status() === PHP_SESSION_NONE) { session_start(); }

// app/helpers.php:149
function csrf_token() {
    if (empty($_SESSION['_csrf'])) { $_SESSION['_csrf'] = bin2hex(random_bytes(32)); }
    return $_SESSION['_csrf'];
}
function csrf_verify() {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') return true;
    $t = $_POST['_csrf'] ?? '';
    if (!$t || !hash_equals($_SESSION['_csrf'] ?? '', $t)) { http_response_code(419); exit(...); }
    return true;
}
```

- ✅ مکانیزم CSRF **صحیح و امن** پیاده‌سازی شده (`random_bytes(32)` + `hash_equals`)
- ✅ در ۱۴ مورد از ۱۶ صفحه استفاده شده (فقط `dashboard` و `export` و `reports` بدون POST)
- ❌ اما **session فقط برای CSRF و Flash** است — نه برای احراز هویت
- ❌ هیچ `login`، `password_hash`، `password_verify`، یا بررسی هویت

### ۷.۳ نتیجهٔ قطعی برای بخش ۳۳ مأموریت (Authentication Architecture)

> مأموریت: «Authentication را از صفر نساز اگر Management System قبلاً سیستم Authentication مناسب دارد. آیا می‌توان Authentication را Shared کرد؟»

**پاسخ: خیر — قابل اشتراک نیست، زیرا اصلاً وجود ندارد.**

**دلیل فنی مستند:**
1. هیچ جدول `users` در `storage/ghatehresan.sqlite` وجود ندارد (تأیید‌شده با `PRAGMA table_info`)
2. هیچ تابع `login/logout/password_*` در کل ۵٬۲۳۶ خط کد یافت نشد
3. هیچ Token، JWT، Session امنیتی یا مکانیزم SSO وجود ندارد
4. Session فعلی صرفاً حامل CSRF token و پیام‌های Flash است

**بنابراین:** HRM **باید** Authentication را از صفر بسازد — اما باید آن را طوری طراحی کند که **قابل استخراج به یک Identity Provider مشترک** باشد (بخش ۱۸).

---

## ۸. Users موجود

# ❌ هیچ Userی در هیچ سیستمی وجود ندارد.

| سیستم | جدول Users | تعداد رکورد | فیلدها |
|---|---|---|---|
| Management System | ❌ جدول ندارد | ۰ | — |
| `ghatehresan-website` | ❌ (فقط Mockup HTML) | ۰ | `نام کاربر · ایمیل · نقش · وضعیت` (در جدول HTML) |
| `website` | ❌ | ۰ | — |

**تنها «نقش» موجود در کل اکوسیستم** در جدول `suppliers` است و ربطی به کاربران ندارد:

```php
// app/db.php:117
role $TXT DEFAULT 'در حال بررسی',

// app/pages/suppliers.php:42
$roleKind = ['تأمین‌کنندهٔ اصلی'=>'green','تأمین‌کنندهٔ پشتیبان'=>'info',
             'فقط اقلام خاص'=>'amber','فعلاً نه'=>'gray','در حال بررسی'=>'gray'][$s['role']] ?? 'gray';
```
این نقشِ **تأمین‌کننده در کسب‌وکار** است، **نه** نقشِ کاربر سیستم.

**نتیجه:** HRM اولین سیستمی است که `users` و `roles` تعریف می‌کند و باید **مرجع آینده** باشد.

---

## ۹. API موجود

# ❌ هیچ APIای وجود ندارد.

### ۹.۱ جست‌وجوی سیستماتیک

```
grep -rniE "json|api|Content-Type|fetch\(|XMLHttpRequest" --include=*.php --include=*.js
```

| نتیجه | تفسیر |
|---|---|
| `app/pages/export.php:142` → `header('Content-Type: text/csv; charset=UTF-8')` | خروجی **CSV**، نه API |
| `app/pages/settings.php:72` → `header('Content-Type: application/octet-stream')` | دانلود **پشتیبان SQLite**، نه API |
| سایر موارد | کلمات کلیدی تصادفی (`fetch` در `fetch()` متد PDO و `fetchColumn()`) |

### ۹.۲ تنها «نقاط پایانی» موجود

| مسیر | نوع | خروجی |
|---|---|---|
| `?p=dashboard` … `?p=settings` | HTML | صفحهٔ کامل Server-Rendered |
| `?p=export&...` | فایل | CSV (دانلود) |
| `?p=settings&download=backup` | فایل | `.sqlite` (دانلود — ⚠️ بدون احراز هویت) |

### ۹.۳ نتیجه

HRM **اولین API اکوسیستم** خواهد بود. این یک مزیت است: می‌توانیم قرارداد را درست تعریف کنیم بدون سازگاری با قرارداد قدیمی.

---

## ۱۰. Database موجود

### ۱۰.۱ Management System

**نوع:** SQLite (پیش‌فرض) یا MySQL — قابل انتخاب با `'driver'` در `app/config.php`
**فایل:** `storage/ghatehresan.sqlite`
**ORM:** ندارد — SQL خام
**Migration:** ندارد — `CREATE TABLE IF NOT EXISTS` در هر request
**Collation پیشنهادی در کامنت کانفیگ:** `utf8mb4_persian_ci`

**فهرست کامل جداول (۱۱ جدول برنامه + ۱ جدول سیستمی):**

| جدول | سطرها | ستون‌ها |
|---|---|---|
| `categories` | **۹** | id, name, sort_order |
| `vehicles` | **۱۲** | id, name, maker, sort_order |
| `suppliers` | ۰ | id, name, contact_person, phone, address, specialty, has_price_list, price_validity, min_order, lead_time, stock_report, return_policy, official_invoice, authenticity, credit_terms, exclusivity, rate_reliability, rate_quality, rate_response, role, notes, last_price_update, active, created_at |
| `products` | ۰ | id, internal_code, part_number, name, category_id, brand, quality, supplier_id, buy_price, sell_price, status, lead_days, stock_qty, has_photo, year_engine, notes, created_at, updated_at |
| `product_vehicles` | ۰ | id, product_id, vehicle_id |
| `customers` | ۰ | id, name, phone, city, address, ctype, vehicle, source, notes, created_at |
| `orders` | ۰ | id, order_no, order_date, customer_id, customer_name, city, status, gateway_fee, shipping_cost, packaging_cost, is_returned, return_reason, notes, created_at |
| `order_items` | ۰ | id, order_id, product_id, product_name, qty, buy_price, sell_price |
| `missed` | ۰ | id, req_date, part_name, vehicle, year_engine, source, quoted_price, followed_up, resolved, notes, created_at |
| `stock_moves` | ۰ | id, product_id, move_date, qty, kind, unit_cost, ref, notes, created_at |
| `settings` | **۵** | k, v |
| `sqlite_sequence` | ۱۰ | (سیستمی) |

**تنها داده‌های موجود:**
- `categories`: فیلتر، مصرفی موتور، روغن و مایعات، ترمز، برقی سبک، کابین و جانبی، جلوبندی، بدنه، سایر
- `vehicles`: پژو ۴۰۵، پژو پارس، پژو ۲۰۶، سمند، دنا، رانا، پراید، تیبا، کوییک، شاهین، ال۹۰، سایر
- `settings`: `gateway_fee_percent=0`, `packaging_cost=0`, `stock_min_sales_90d=3`, `business_name=قطعه‌رسان`, `schema_version=1`

> **نکتهٔ مهم:** پایگاه داده **تقریباً خالی** است (فقط دادهٔ پایهٔ Seed). هیچ دادهٔ عملیاتی واقعی وجود ندارد.

**ایندکس‌ها (۱۴ مورد):** `idx_prod_name`, `idx_prod_pn`, `idx_prod_cat`, `idx_prod_sup`, `idx_prod_status`, `idx_pv_prod`, `idx_pv_veh`, `idx_ord_date`, `idx_ord_status`, `idx_ord_cust`, `idx_oi_order`, `idx_oi_prod`, `idx_missed_date`, `idx_sm_prod`

**مکانیزم ارتقای schema:**
```php
// app/db.php:272 — افزودن ستون‌های جدید بدون تخریب داده
$patch = [
    'customers' => ['address'=>"$LONG NULL", 'ctype'=>"$TXT NULL",
                    'vehicle'=>"$TXT NULL", 'source'=>"$TXT NULL"],
    'vehicles'  => ['maker'   => "$TXT NULL"],
    'products'  => ['quality' => "$TXT NULL"],
];
```
+ انتقال داده از `customers.type` → `customers.ctype`

### ۱۰.۲ Website

❌ **هیچ پایگاه داده‌ای ندارد.**

### ۱۰.۳ نتیجه برای HRM

هیچ Schemaی برای سازگاری وجود ندارد. HRM می‌تواند یک Schema نرمال و مدرن طراحی کند — اما باید **الگوی ارتقای امن** (`table_columns()` + `ALTER TABLE`) را از Management System بیاموزد و به Migration واقعی ارتقا دهد.

---

## ۱۱. داده‌های پرسنلی موجود (Employee Data)

# ❌ هیچ دادهٔ پرسنلی در هیچ سیستمی وجود ندارد.

| مورد | جست‌وجو | نتیجه |
|---|---|---|
| جدول `employees` | `PRAGMA table_info` روی همهٔ جداول | ❌ وجود ندارد |
| فیلد نام/نام‌خانوادگی پرسنل | بررسی همهٔ ستون‌ها | ❌ (فقط `customers.name` و `suppliers.contact_person` که پرسنل نیستند) |
| کد ملی | `grep` | ❌ |
| حقوق/دستمزد | `grep` | ❌ |
| شماره حساب | `grep` | ❌ |
| تاریخ استخدام | `grep` | ❌ |
| سمت/پوزیشن | `grep` | ❌ |
| دپارتمان | `grep` | ❌ |

**تنها موجودیت‌های «شخص» در سیستم:**

| موجودیت | جدول | ماهیت | مالک |
|---|---|---|---|
| مشتری | `customers` | شخص حقیقی/حقوقی خریدار | Management System |
| رابط تأمین‌کننده | `suppliers.contact_person` | شخص contact در شرکت تأمین‌کننده | Management System |

> ⚠️ **هر دو «طرف معامله» هستند، نه پرسنل.**
> نباید با `Employee` اشتباه یا ادغام شوند — این دقیقاً همان تلهٔ Duplicate Data است که مأموریت هشدار داده است.

**نتیجه:** Migration از Management System به HRM **موضوعیت ندارد** (بخش Migration مأموریت: «اگر Management System اطلاعات پرسنلی دارد» → **ندارد**).

---

## ۱۲. فرصت‌های Integration

با توجه به نبود API و Auth در همه‌جا، integration باید **از نو طراحی** شود. چهار فرصت واقعی:

### ۱۲.۱ Brand → HRM (یک‌طرفه، فایل)
```
brand-ghatehresan/logo/*.svg        → منبع دارایی
brand-ghatehresan (brand-book.html) → منبع tokens
        ↓ (باید ماشین‌خوان شود)
   design-tokens.json   → Tailwind @theme → CSS Custom Properties
```
**فرصت:** تبدیل `:root` در `brand-book.html` به یک فایل `design-tokens.json` و تولید خودکار تم Tailwind. این باعث می‌شود انحراف از برند **قابل اندازه‌گیری** شود.

### ۱۲.۲ Management System ← → HRM
**فرصت واقعیِ واحد:** «چه کسی این سفارش را ثبت/پیگیری کرده؟»

امروز `orders` هیچ فیلد مالک/عامل ندارد:
```sql
orders: id, order_no, order_date, customer_id, customer_name, city, status,
        gateway_fee, shipping_cost, packaging_cost, is_returned, return_reason,
        notes, created_at
-- ❌ هیچ created_by / assigned_to / employee_id ندارد
```
**پیشنهاد:** افزودن `orders.created_by_user_id` در Management System و نگاشت به `HRM.users.id`.
این **تغییر در Management System** است و باید در مخزن خودش انجام شود (خارج از scope این مخزن).

**سایر موارد:**

| مورد | امکان | روش |
|---|---|---|
| مدیر دپارتمان در HRM ↔ تأیید در Management System | 🔶 آینده | API توکن‌دار |
| کارمزد فروش / پورسانت → Payroll | 🔶 آینده | HRM می‌خواند، Management System مالک است |
| تقویم کاری / شیفت پرسنل انبار ↔ موجودی | 🔷 دور | — |

### ۱۲.۳ Website ← → HRM (Careers)
```
Website: صفحهٔ «فرصت‌های شغلی»  →  GET  /api/v1/public/careers/job-openings
Website: فرم ارسال رزومه        →  POST /api/v1/public/careers/applications
                                    (ناشناس، دارای Rate Limit و Captcha)
HRM: مدیریت رزومه، مصاحبه، ارزیابی  ← دادهٔ خصوصی، هرگز به Website برنمی‌گردد
```
این دقیقاً منطبق با MODULE 31/32 مأموریت است. Website فعلاً وجود ندارد، پس این یک **قرارداد آماده** است.

### ۱۲.۴ Shared Package (فرصت معماری)
این توابع در Management System وجود دارند و HRM هم به آن‌ها نیاز دارد:
```
gregorian_to_jalali() · jalali_to_gregorian() · jdate() · jmonth_range()
fa_digits() · en_digits() · money() · to_int() · e()
```
**پیشنهاد:** استخراج به یک بستهٔ مشترک (مثلاً `ghatehresan/persian-support` برای PHP و معادل TS برای Frontend) تا رفتار تاریخ/عدد در هر دو سیستم یکسان باشد.

---

## ۱۳. ریسک‌های امنیتی

### ۱۳.۱ 🔴 بحرانی (Critical)

| # | ریسک | شاهد | پیامد | اولویت |
|---|---|---|---|---|
| C-1 | **Management System هیچ احراز هویتی ندارد** | هیچ `login`/`password_verify` در کل کد | هر کسی با URL می‌تواند تمام داده‌های مشتریان، سفارش‌ها، قیمت‌های خرید و سود را ببیند و **تغییر دهد** | فوری |
| C-2 | **دانلود پشتیبان کامل DB بدون احراز هویت** | `settings.php:68` — `if (get('download') === 'backup' && DB_DRIVER === 'sqlite')` | `?p=settings&download=backup` کل پایگاه داده (شامل مشتریان و آدرس‌ها) را برای هر کسی دانلود می‌کند | فوری |
| C-3 | **حذف تمام داده‌ها بدون احراز هویت** | `settings.php:55` — `if ($act === 'demo_clear')` + `settings.php:65` — `DELETE FROM customers/orders/...` | پاک‌سازی کل داده‌های عملیاتی توسط هر بازدیدکننده | فوری |
| C-4 | **هیچ Audit Log ندارد** | — | تغییرات غیرقابل ردیابی؛ برای HR «چه کسی حقوق را تغییر داد» نامشخص می‌ماند | فوری (در HRM) |

### ۱۳.۲ 🟠 بالا (High)

| # | ریسک | شاهد | پیامد |
|---|---|---|---|
| H-1 | محافظت DB فقط با `.htaccess` | `storage/.htaccess` → `Require all denied` | روی **nginx** (که `.htaccess` را نادیده می‌گیرد) بی‌اثر است؛ نیاز به قانون صریح وب‌سرور |
| H-2 | `display_errors` قابل فعال‌سازی | `config.php:41` — `'debug' => false` | اگر در production روشن شود، مسیر فایل‌ها و ساختار DB افشا می‌شود |
| H-3 | تاریخ‌ها به‌صورت **زمان محلی naive** | `db.php:91` — `datetime('now','localtime')` | ناهماهنگی زمانی در تغییر ساعت تابستانی و جابه‌جایی سرور؛ برای حضور‌و‌غیاب فاجعه‌بار است |
| H-4 | مبالغ مالی به‌صورت `INTEGER` بدون واحد/ارز | `orders.gateway_fee BIGINT` | ابهام ریال/تومان؛ خطای محاسباتی در Payroll |
| H-5 | Website = قالب استاتیک بدون Backend و بدون CSP | — | تزریق اسکریپت در صورت الحاق کد سفارشی |
| H-6 | دارایی‌های قالب شخص ثالث در Website | `images/logo/digikala.webp`, `zarinpal`, `enamad` | ریسک حقوقی/اعتباری |

### ۱۳.۳ 🟡 متوسط (Medium)

| # | ریسک | توضیح |
|---|---|---|
| M-1 | CSRF فقط روی POST | `csrf_verify()` بلافاصله برای غیر-POST برمی‌گردد؛ عملیات حساس باید POST باشند |
| M-2 | بدون Rate Limiting | امکان حملهٔ Brute-force در آینده (وقتی Login اضافه شود) |
| M-3 | بدون هدرهای امنیتی | هیچ `X-Frame-Options`، `X-Content-Type-Options`، `Referrer-Policy`، `CSP` |
| M-4 | بدون مدیریت Secrets | رمز DB در `app/config.php` به‌صورت متن ساده در مخزن |
| M-5 | `migrate()` در هر request اجرا می‌شود | `public/index.php:36` — سربار و سطح حملهٔ غیرضروری |
| M-6 | بدون نگارش schema واقعی | `schema_version=1` فقط یک رشته است، بدون جدول `migrations` |

### ۱۳.۴ ✅ نقاط قوت موجود (باید حفظ شود)

| مورد | شاهد |
|---|---|
| **SQL Injection** — استفادهٔ صحیح از Prepared Statements | `q()`, `one()`, `all()` همیشه با `?` placeholder؛ **هیچ** الحاق مستقیم `$_GET`/`$_POST` به SQL یافت نشد |
| **XSS** — خروجی فرار‌شده | `e()` = `htmlspecialchars(..., ENT_QUOTES \| ENT_SUBSTITUTE, 'UTF-8')`؛ الگوی `$v = fn($k,$d='') => e($row[$k] ?? $d)` |
| **CSRF** — مکانیزم درست | `random_bytes(32)` + `hash_equals()`، در ۱۴ صفحه از ۱۶ |
| **ترمیم Unicode** | `ENT_SUBSTITUTE` جلوی تولید خروجی نامعتبر UTF-8 را می‌گیرد |
| **نمایش ایمن خطا** | پیام خطای DB به کاربر اطلاعات زیرساخت نمی‌دهد (فقط راهنما) |

---

## ۱۴. مالکیت داده (Data Ownership)

### ۱۴.۱ ماتریس مالکیت

| Entity | **Owner (Source of Truth)** | Reader | Writer | Public |
|---|---|---|---|---|
| `User` (هویت/اعتبارنامه) | **HRM — Identity Context** | HRM · (آینده: Management System) | HRM | ❌ خیر |
| `Employee` (پروفایل پرسنلی) | **HRM** | HR/Admin · Manager (محدود به تیم) · خود کارمند | HR/Admin | ❌ خیر |
| `Department` / `Position` / `Branch` | **HRM — Organization** | HRM · (آینده: Management System، فقط خواندن) | HR/Admin | ❌ خیر |
| `Contract` | **HRM** | HR/Admin · مالی (محدود) · خود کارمند (قرارداد خود) | HR/Admin | ❌ خیر |
| `Salary / Compensation` | **HRM** | فقط دارای `employee.salary.view` | فقط دارای `employee.salary.edit` | ❌ **هرگز** |
| `Attendance` | **HRM** | HR · Manager (تیم) · خود کارمند | HR · سیستم دستگاه · خود کارمند (محدود) | ❌ خیر |
| `Leave` | **HRM** | HR · Manager (تیم) · خود کارمند | خود کارمند (درخواست) · Manager (تأیید) | ❌ خیر |
| `Document` (مدارک پرسنلی) | **HRM** | دارای permission مشخص | HR/Admin · خود کارمند (مدارک خود) | ❌ **هرگز** (URL امضاشده) |
| `Recruitment / Application` | **HRM** | HR · مصاحبه‌کننده (محدود) | HRM · Website (فقط ایجاد، ناشناس) | ⚠️ فقط **ایجاد** |
| `JobOpening` (آگهی شغلی) | **HRM** | همه | HR | ✅ **بله** (فقط موارد منتشرشده) |
| `AuditLog` | **HRM** | Auditor · Admin | سیستم (فقط افزودن) | ❌ خیر — **حذف‌ناپذیر** |
| `Customer` | **Management System** | HRM ❌ نیاز ندارد | Management System | ❌ خیر |
| `Product` / `Order` / `Stock` / `Supplier` | **Management System** | HRM ❌ نیاز ندارد | Management System | محصولات: ✅ عمومی (از طریق Website) |
| `Brand assets` | **Brand Repository** | همه (مصرف‌کننده) | Brand Repository | ✅ بله |

### ۱۴.۲ قانون جلوگیری از Duplicate Data (مأموریت)

> مأموریت: «اگر Management System User identity را مدیریت می‌کند، HRM نباید یک User system موازی ناسازگار ایجاد کند.»

**وضعیت واقعی معکوس است:** Management System اصلاً User ندارد. بنابراین:

```
                    ┌────────────────────────────┐
                    │   HRM — Identity Context   │
                    │   users (هویت/اعتبارنامه)  │  ← Source of Truth آینده
                    └─────────────┬──────────────┘
                                  │ 1:1 (nullable)
                                  ▼
                    ┌────────────────────────────┐
                    │   HRM — People Context     │
                    │   employees (پروفایل HR)   │  ← Source of Truth پرسنلی
                    └────────────────────────────┘

   ❌ هرگز Customer/Supplier را به Employee تبدیل نکن
   ❌ هرگز Employee را در Management System کپی نکن
   ✅ Management System در آینده فقط user_id را reference می‌دهد
```

**تفکیک الزامی:** `User` (چه کسی می‌تواند وارد شود) ≠ `Employee` (چه کسی کارمند است).
- یک `User` می‌تواند بدون `Employee` باشد (مثلاً Auditor خارجی، یا ادمین فنی)
- یک `Employee` می‌تواند بدون `User` باشد (پرسنلی که هنوز دسترسی نگرفته‌اند)
- این تفکیک برای Multi-company و پیمانکاران آینده ضروری است

---

## ۱۵. معماری پیشنهادی HRM

### ۱۵.۱ اصول

| اصل | تحقق |
|---|---|
| **Modular** | ماژول‌های HRM در Bounded Contextهای مجزا (`Organization`, `People`, `Time`, `Talent`, …) |
| **Secure** | Permission در Backend اِعمال می‌شود (Policies)؛ Frontend فقط نمایش را پنهان می‌کند |
| **Scalable** | صف (Queue) برای کارهای پس‌زمینه؛ کش برای گزارش‌ها |
| **API-first** | تمام منطق در Application/Domain Layer؛ `/api/v1` در کنار UI روی همان سرویس‌ها |
| **Role-based** | RBAC پویا با Permissionهای granular (نه نقش‌های hard-code) |
| **Audit-friendly** | Audit Log در لایهٔ میان‌افزار + Event، حذف‌ناپذیر |
| **Multi-department** | ساختار سازمانی درختی پویا، هیچ چیز hard-code نمی‌شود |

### ۱۵.۲ Stack پیشنهادی (با استدلال)

| لایه | انتخاب | دلیل (مستند به Audit) |
|---|---|---|
| **Backend** | **PHP 8.3 + Laravel 12** | ۱) تنها زبان سمت‌سرور موجود در اکوسیستم PHP است (بخش ۳)؛ ۲) Laravel به‌صورت بومیRBAC (Gate/Policy)، Sanctum، Queue، Validation، Encryption-at-rest، Rate Limiting و Signed URL دارد — یعنی دقیقاً چیزهایی که MODULE 34 می‌خواهد بدون اختراع دوباره؛ ۳) روی همان زیرساخت Management System (XAMPP/cPanel — شواهد: `config.php` به phpMyAdmin اشاره می‌کند) قابل استقرار است |
| **Database** | **MySQL 8.0 / MariaDB 10.6+** با `utf8mb4_persian_ci` ✅ **تأیید‌شده** | هم‌راستا با `config.php:8` که این Collation را توصیه کرده؛ در دسترس روی XAMPP و هاست اشتراکی； پشتیبانی از CTE بازگشتی (درخت سازمان) و JSON. **PostgreSQL کنار گذاشته شد** — روی XAMPP در دسترس نیست |
| **Frontend** | **React 19 + TypeScript + Vite + Tailwind v4** | Tailwind v4 همان نسخهٔ Website است → هم‌سویی ابزار؛ TypeScript نوع‌آمیزی را که مأموریت الزام کرده فراهم می‌کند； مناسب برای UI پیچیدهٔ HR (تقویم، پایپ‌لاین، شبکهٔ حضور‌و‌غیاب) |
| **اتصال Frontend** | **Inertia.js 2** (توصیه) **یا** React SPA + API | Inertia: یک استقرار، Session/Cookie بدون CORS، بدون تکرار کنترلر. SPA: انطباق کامل‌تر با «API-first». هر دو روی یک لایهٔ Application مشترک |
| **Auth** | **Laravel Sanctum** — Cookie (HttpOnly/Secure/SameSite) برای کاربر اول‌شخص + Token برای سرویس‌ها | بدون نیاز به مدیریت Refresh Token در Frontend； توکن برای integration با Management System/Website |
| **Cache / Queue** | درایور **`database`** روی XAMPP · Redis در صورت VPS | MODULE 40 — اعلان‌ها، انقضای قرارداد، گزارش‌ها. **وابستگی سخت به Redis ممنوع** (روی XAMPP/هاست اشتراکی در دسترس نیست) |
| **Storage** | دیسک **خصوصی خارج از webroot** + URLهای امضا‌شدهٔ موقت | MODULE 37 — «فایل‌های خصوصی نباید از مسیر public قابل دانلود باشند» |
| **تست** | Pest/PHPUnit (Unit+Feature) · Vitest (Unit) · Playwright (E2E) | MODULE 45/46 |
| **CI/CD** | GitHub Actions | MODULE 50 |

> **توصیهٔ به‌روزشده با توجه به تصمیم XAMPP:**
> با تأیید توسعه روی XAMPP، **Laravel + Blade + Alpine.js + Tailwind v4** را به‌عنوان گزینهٔ پیشنهادیِ فاز بنیان توصیه می‌کنم —
> زیرا (۱) به Node در محیط توسعه نیاز ندارد، (۲) با مهارت فعلی تیم (PHP خام) هم‌خوان‌تر است،
> (۳) روی هاست اشتراکی بدون دردسر اجرا می‌شود.
>
> در هر دو حالت **لایهٔ Application و `/api/v1` یکسان** است، بنابراین ارتقا به Inertia/React
> بعداً یک تغییر در لایهٔ نمایش است و بازنویسی Backend نمی‌طلبد.
> این تصمیم (D-2) در بخش ۲۵٫۲ برای تأیید نهایی باز است.

### ۱۵.۳ معماری لایه‌ای

```
┌──────────────────────────────────────────────────────────────┐
│  Presentation                                                │
│  ┌────────────────────────┐   ┌───────────────────────────┐  │
│  │ Web UI (React/Inertia) │   │ REST API  /api/v1         │  │
│  │ RTL · Jalali · Vazirmatn│   │ Sanctum · OpenAPI         │  │
│  └───────────┬────────────┘   └─────────────┬─────────────┘  │
└──────────────┼──────────────────────────────┼────────────────┘
               │                              │
               └──────────────┬───────────────┘
                              ▼
┌──────────────────────────────────────────────────────────────┐
│  HTTP Layer — Controllers (thin)                             │
│  · FormRequest (اعتبارسنجی)  · Policy (Authorization)        │
│  · Resource/Transformer (جلوگیری از Over-fetching)           │
└──────────────────────────┬───────────────────────────────────┘
                           ▼
┌──────────────────────────────────────────────────────────────┐
│  Application Layer — Services / Actions (منطق کسب‌وکار)      │
│  EmployeeService · LeaveService · AttendanceService ·        │
│  RecruitmentService · WorkflowService · ReportService        │
│  · DB Transaction  · Event Dispatch  · Audit Record          │
└──────────────────────────┬───────────────────────────────────┘
                           ▼
┌──────────────────────────────────────────────────────────────┐
│  Domain Layer — Bounded Contexts                             │
│  ┌────────────┬────────────┬────────────┬─────────────────┐  │
│  │ Identity   │ Organization│ People    │ Time (Attendance│  │
│  │            │             │           │  / Leave / Shift)│  │
│  ├────────────┼────────────┼────────────┼─────────────────┤  │
│  │ Talent     │ Documents  │ Assets     │ Performance     │  │
│  │ (ATS)      │            │            │                 │  │
│  ├────────────┼────────────┼────────────┼─────────────────┤  │
│  │ Requests   │ Notification│ Reporting │ Audit           │  │
│  └────────────┴────────────┴────────────┴─────────────────┘  │
└──────────────────────────┬───────────────────────────────────┘
                           ▼
┌──────────────────────────────────────────────────────────────┐
│  Infrastructure                                              │
│  Eloquent Models · Migrations · Storage (private) ·          │
│  Queue/Redis · Mail/SMS · Jalali Service · Encryption        │
└──────────────────────────────────────────────────────────────┘
```

### ۱۵.۴ Bounded Contextها و نگاشت به Moduleهای مأموریت

| Context | Moduleهای مأموریت | موجودیت‌های اصلی |
|---|---|---|
| **Identity** | M2 (بخشی)، M8، M33، M34 | `User`, `Role`, `Permission`, `MFAMethod`, `Session` |
| **Organization** | M1، M28 | `Company`, `Branch`, `Department`, `Team`, `Position`, `OrgUnit` |
| **People** | M2، M3، M35 | `Employee`, `EmployeeContact`, `EmployeeEmployment`, `LifecycleState` |
| **Contracts** | M13، M12 (پایه) | `Contract`, `ContractType`, `Compensation` |
| **Time** | M5، M6، M7، M26، M27 | `Attendance`, `Shift`, `ShiftTemplate`, `LeaveRequest`, `LeaveType`, `Holiday`, `WorkSchedule` |
| **Talent (ATS)** | M4، M32 | `JobOpening`, `Applicant`, `Application`, `Interview`, `Evaluation`, `Offer` |
| **Documents** | M14، M37 | `Document`, `DocumentType`, `DocumentVersion` |
| **Performance** | M15 | `Goal`, `KPI`, `ReviewCycle`, `Review`, `Feedback` |
| **Training** | M16 | `Course`, `Training`, `Certificate` |
| **Assets** | M17 | `Asset`, `AssetCategory`, `AssetAssignment` |
| **Requests** | M18، M10 | `RequestType`, `Request`, `WorkflowDefinition`, `WorkflowStep`, `Approval` |
| **Notification** | M19، M40 | `Notification`, `NotificationChannel`, `NotificationPreference` |
| **Reporting** | M11، M21، M22 | (Read Models / Views) + `SavedReport`, `ExportJob` |
| **Audit** | M20، M39 | `AuditLog` (append-only) |

### ۱۵.۵ Multi-Company / Multi-Branch (MODULE 28)

**تصمیم:** زیرساخت Multi-Company از روز اول در Schema (`company_id` روی موجودیت‌های سازمانی)، اما **بدون** اِعمال Tenant Isolation در لایهٔ Application تا زمانی که واقعاً نیاز شود.

| سطح | وضعیت |
|---|---|
| `companies` جدول دارد | ✅ از Milestone 3 |
| `branch_id` / درخت دپارتمان | ✅ پویا، hard-code نمی‌شود |
| Tenant Isolation (Global Scope) | 🔶 **غیرفعال** تا نیاز واقعی — یک شرکت در حال حاضر |
| Row-Level Security در DB | 🔶 آماده، بعداً فعال می‌شود |

**دلیل:** مأموریت صریحاً گفته «Multi-tenancy را فقط در صورتی پیاده‌سازی کن که واقعاً برای نیاز فعلی لازم است». یک شرکت داریم → isolation اِعمال نمی‌شود، اما **ستون‌ها و محدودیت‌ها آماده‌اند** تا بعداً migration دردناک نباشد.

---

## ۱۶. مدل پایگاه داده پیشنهادی

> **نکته:** این نمای کلیِ روابط است. اسکیمای ستون‌به‌ستون در `DATABASE.md` (پس از تأیید معماری) نوشته خواهد شد.

### ۱۶.۱ هسته (Core)

```
companies ──┬── branches
            └── departments (درخت: parent_id)

Identity:
users ──┬──< user_roles >── roles ──< role_permissions >── permissions
        ├──< mfa_methods
        ├──< sessions
        └──< personal_access_tokens

People:
employees ──┬── user_id        → users.id        (1:1, nullable)
            ├── company_id     → companies.id
            ├── department_id  → departments.id
            ├── position_id    → positions.id
            ├── manager_id     → employees.id    (خودارجاع)
            ├── branch_id      → branches.id
            └── work_location_id

Employee Relations (طبق مأموریت M36):
Employee
 ├── Department
 ├── Position
 ├── Manager
 ├── Contracts        (1:N)
 ├── Documents        (1:N)
 ├── Attendance       (1:N)
 ├── Leaves           (1:N)
 ├── Assets           (1:N, از طریق asset_assignments)
 ├── Reviews          (1:N)
 └── Requests         (1:N)
```

### ۱۶.۲ نمودار ساده‌شده

```
                       ┌──────────┐
                       │ companies│
                       └────┬─────┘
              ┌─────────────┼─────────────┐
              ▼             ▼             ▼
        ┌──────────┐  ┌───────────┐  ┌──────────┐
        │ branches │  │departments│  │positions │
        └────┬─────┘  │(parent_id)│  └────┬─────┘
             │        └─────┬─────┘       │
             │              │             │
             └──────────┬───┴─────────────┘
                        ▼
                  ┌───────────┐         ┌────────┐
                  │ employees │◄───────►│ users  │
                  │ manager_id│  1:1    └───┬────┘
                  └─────┬─────┘  (nullable) │
                        │                   ├──< roles >──< permissions
     ┌──────────┬───────┼───────┬───────────┤
     ▼          ▼       ▼       ▼           ▼
┌─────────┐┌────────┐┌──────┐┌────────┐┌──────────┐
│contracts││documents││leaves││attend. ││asset_asg │
└─────────┘└────────┘└──┬───┘└───┬────┘└──────────┘
                        │        │
                        ▼        ▼
              ┌──────────────┐ ┌─────────────┐
              │ leave_types  │ │shifts/      │
              │ leave_balances│ │shift_assign│
              └──────────────┘ └─────────────┘

Talent:  job_openings ──< applications ──< applicants
                     └──< interviews ──< evaluations ──< offers

Requests: request_types ──< requests ──< approvals
          workflow_definitions ──< workflow_steps

Cross:   audit_logs (append-only) · notifications · holidays
```

### ۱۶.۳ تصمیمات کلیدی Schema

| تصمیم | انتخاب | دلیل |
|---|---|---|
| **Soft Delete** | ✅ روی: `employees`, `departments`, `positions`, `contracts`, `documents`, `job_openings` | مأموریت M36: «از حذف داده‌ها در موارد حساس خودداری کن» |
| **Hard Delete** | روی داده‌های فنی (`sessions`, `personal_access_tokens`, `password_reset_tokens`, کش) | نگه‌داری بی‌دلیل دادهٔ حساس امنیتی ممنوع |
| **Audit Log** | **Append-only** — بدون `updated_at`، بدون `deleted_at`، بدون مسیر حذف | مأموریت M20: «نباید توسط کاربر عادی قابل حذف باشد» |
| **نوع تاریخ** | `DATE` برای تاریخ مدنی (تولد، استخدام) · `DATETIME`/`TIMESTAMP` به **UTC** برای رویدادها | رفع نقص H-3 در Management System |
| **منطقهٔ زمانی** | ذخیره **UTC**؛ نمایش **Asia/Tehran**؛ تبدیل Jalali **فقط** در Presentation | مأموریت M27 |
| **مبالغ** | `DECIMAL(15,2)` + ستون `currency` (پیش‌فرض `IRR`) | رفع نقص H-4 |
| **کد ملی / شماره حساب** | ستون **رمزنگاری‌شده** در سطح کاربرد + Partial Index برای جست‌وجو | مأموریت M34/M35 |
| **درخت سازمان** | `parent_id` + CTE بازگشتی (+ `materialized path` برای خواندن سریع) | مأموریت M1: «نباید Hard-code شود» |
| **ضمائم فایل** | جدول `documents` با `disk`, `path`, `checksum`, `size`, `mime`؛ فایل واقعی در دیسک خصوصی | مأموریت M37 |
| **ایندکس‌ها** | روی همهٔ FKها + فیلدهای جست‌وجو + ترکیبیِ `(company_id, department_id, status)` | — |
| **Migration** | جدول `migrations` واقعی (برخلاف `CREATE TABLE IF NOT EXISTS` در هر request) | رفع نقص M-5/M-6 |

### ۱۶.۴ جداسازی داده‌های حساس (MODULE 35)

اطلاعات زیر در جداول/ستون‌های **جداگانه** با Permission مستقل:

| داده | نگهداری | Permission | در API عمومی |
|---|---|---|---|
| کد ملی | `employees.national_id` — **رمزنگاری‌شده** | `employee.national_id.view` | ❌ هرگز |
| حقوق | `compensations.*` — جدول جدا | `employee.salary.view` / `.edit` | ❌ هرگز |
| شماره حساب | `employee_bank_accounts.*` — **رمزنگاری‌شده** | `employee.bank.view` | ❌ هرگز |
| قرارداد | `contracts.*` | `contract.view` | ❌ |
| آدرس / تلفن | `employee_contacts.*` | `employee.contact.view` | ⚠️ جزئی (فقط شماره داخلی) |
| مدارک شخصی | `documents.*` با `visibility` | `document.view.{type}` | ❌ (URL امضاشده) |
| ارزیابی عملکرد | `reviews.*` | `performance.review.view` | ❌ |

**مکانیزم ضد Over-fetching:** پاسخ‌های API از طریق **Resource/Transformer** عبور می‌کنند که فیلدهای حساس را **به‌طور پیش‌فرض حذف** می‌کند؛ نمایش آن‌ها فقط با Permission صریح و پارامتر `?include=` انجام می‌شود.

---

## ۱۷. معماری API

### ۱۷.۱ اصول

| اصل | تحقق |
|---|---|
| Versioning | `/api/v1/` — تغییرات شکننده نیازمند `/api/v2/` (MODULE 48) |
| استایل | REST روی منابع + Actionهای صریح برای انتقال وضعیت (`POST /leave-requests/{id}/approve`) |
| قالب پاسخ | envelope یکسان: `{ data, meta, links, errors }` |
| صفحه‌بندی | cursor-based برای جداول بزرگ، offset-based برای سایر |
| فیلتر/مرتب‌سازی | پارامترهای query استاندارد: `?filter[status]=active&sort=-hire_date` |
| نرخ‌محدودیت | روی همهٔ مسیرها؛ سخت‌گیرانه‌تر روی auth و endpointهای عمومی |
| مستندات | **OpenAPI 3.1** تولید‌شده از کد + صفحهٔ Swagger UI در `/docs` (MODULE 47) |
| خطاها | کدهای HTTP معنادار + `error.code` ماشین‌خوان + پیام فارسی برای کاربر |
| Idempotency | هدر `Idempotency-Key` برای عملیات ایجاد/تأیید |

### ۱۷.۲ نقاط پایانی پیشنهادی (منطبق بر MODULE 29)

```
احراز هویت
  POST   /api/v1/auth/login
  POST   /api/v1/auth/logout
  POST   /api/v1/auth/refresh
  POST   /api/v1/auth/forgot-password
  POST   /api/v1/auth/reset-password
  POST   /api/v1/auth/mfa/verify
  GET    /api/v1/auth/me

سازمان (Organization)
  GET    /api/v1/companies
  GET    /api/v1/branches
  GET    /api/v1/departments              (درخت، ?tree=1)
  POST   /api/v1/departments
  PATCH  /api/v1/departments/{id}
  DELETE /api/v1/departments/{id}         (soft)
  GET    /api/v1/positions

پرسنل (People)
  GET    /api/v1/employees                (Permission-filtered + Paginated)
  POST   /api/v1/employees
  GET    /api/v1/employees/{id}           (بدون فیلد حساس مگر با permission)
  PATCH  /api/v1/employees/{id}
  DELETE /api/v1/employees/{id}           (soft)
  POST   /api/v1/employees/{id}/lifecycle/{transition}
  GET    /api/v1/employees/{id}/contracts
  GET    /api/v1/employees/{id}/documents
  GET    /api/v1/employees/{id}/attendance
  GET    /api/v1/employees/{id}/leaves
  GET    /api/v1/employees/{id}/assets
  GET    /api/v1/employees/{id}/reviews
  GET    /api/v1/employees/{id}/requests

زمان (Attendance / Shift / Leave)
  GET/POST    /api/v1/attendance
  POST        /api/v1/attendance/check-in
  POST        /api/v1/attendance/check-out
  GET/POST    /api/v1/shifts
  GET/POST    /api/v1/shift-assignments
  GET/POST    /api/v1/leave-requests
  POST        /api/v1/leave-requests/{id}/approve
  POST        /api/v1/leave-requests/{id}/reject
  GET/POST    /api/v1/leave-types
  GET/POST    /api/v1/holidays
  GET         /api/v1/calendar            (MODULE 25)

قراردادها / مدارک
  GET/POST/PATCH  /api/v1/contracts
  GET/POST/PATCH  /api/v1/documents
  GET             /api/v1/documents/{id}/download   (URL امضاشدهٔ موقت — M37)
  GET             /api/v1/documents/{id}/versions

جذب و استخدام (ATS)
  GET/POST/PATCH      /api/v1/recruitment/job-openings
  GET/POST/PATCH      /api/v1/recruitment/applicants
  GET/POST/PATCH      /api/v1/recruitment/applications
  POST                /api/v1/recruitment/applications/{id}/stage
  GET/POST            /api/v1/recruitment/interviews
  GET/POST            /api/v1/recruitment/evaluations
  GET/POST            /api/v1/recruitment/offers

درخواست‌ها / گردش‌کار
  GET/POST        /api/v1/requests
  POST            /api/v1/requests/{id}/submit
  POST            /api/v1/requests/{id}/approve
  POST            /api/v1/requests/{id}/reject
  GET             /api/v1/requests/pending-approvals   (MODULE 10)

عملکرد / آموزش / دارایی‌ها
  GET/POST/PATCH  /api/v1/performance/{goals,kpis,review-cycles,reviews,feedback}
  GET/POST/PATCH  /api/v1/training/{courses,trainings,certificates}
  GET/POST/PATCH  /api/v1/assets/{assets,assignments}

اعلان‌ها / گزارش‌ها / جست‌وجو
  GET/PATCH       /api/v1/notifications
  GET/POST        /api/v1/reports/{employees,attendance,leave,recruitment,turnover}
  POST            /api/v1/reports/export         (CSV/Excel/PDF — M21)
  GET             /api/v1/search?q=              (Permission-filtered — M22)

مدیریت دسترسی
  GET/POST/PATCH/DELETE  /api/v1/roles
  GET/POST/PATCH/DELETE  /api/v1/permissions
  POST                   /api/v1/users/{id}/roles

حسابرسی
  GET             /api/v1/audit-logs             (فقط Auditor/Admin)

مسیرهای عمومی (بدون احراز هویت — Rate-limited)
  GET             /api/v1/public/careers/job-openings
  POST            /api/v1/public/careers/applications

یکپارچه‌سازی (Service Token)
  GET             /api/v1/integration/directory/employees
  POST            /api/v1/auth/introspect
```

> فهرست نهایی پس از انتخاب Framework قطعی می‌شود (طبق مأموریت)، و در `API.md` + OpenAPI مستند خواهد شد.

---

## ۱۸. معماری احراز هویت (Authentication)

### ۱۸.۱ تصمیم

> مأموریت M33: «Authentication را از صفر نساز اگر Management System قبلاً سیستم Authentication مناسب دارد. آیا می‌توان Shared کرد؟ اگر خیر، دلیل فنی را مستند کن.»

**پاسخ: خیر — قابل اشتراک نیست.**
**دلیل فنی (مستند در بخش ۷):** هیچ User Model، هیچ Token، هیچ Session امنیتی، هیچ OAuth/SSO و هیچ مکانیزم MFA در هیچ‌یک از سه سیستم وجود ندارد. Session موجود در Management System صرفاً حامل CSRF token و پیام‌های Flash است (`helpers.php:149-167`).

**بنابراین: HRM باید Authentication را بسازد — اما آن را **SSO-Ready** طراحی می‌کند.**

### ۱۸.۲ طرح

```
┌─────────────────────────────────────────────────────────────┐
│                    HRM — Identity Context                   │
│  ┌───────────────────────────────────────────────────────┐  │
│  │ users · password (Argon2id) · mfa_methods · sessions   │  │
│  │ personal_access_tokens (PAT) · password_reset_tokens   │  │
│  └───────────────────────────────────────────────────────┘  │
│                            │                                │
│         ┌──────────────────┼──────────────────┐             │
│         ▼                  ▼                  ▼             │
│  ┌─────────────┐   ┌──────────────┐   ┌─────────────────┐  │
│  │ First-party │   │  Service /   │   │   SSO (آینده)   │  │
│  │ Cookie/Session│ │  Integration │   │                 │  │
│  │ Sanctum SPA │   │ Bearer Token │   │ OIDC یا         │  │
│  │ HttpOnly    │   │ + Scope      │   │ Token Introspect│  │
│  │ Secure      │   │ + Expiry     │   │                 │  │
│  │ SameSite=Lax│   │ + IP Allowlist│  │                 │  │
│  └─────────────┘   └──────────────┘   └─────────────────┘  │
│         │                  │                  │             │
└─────────┼──────────────────┼──────────────────┼─────────────┘
          ▼                  ▼                  ▼
     Web UI HRM        Management System    Website (Careers)
     (همان دامنه)      (آینده)              (آینده)
```

### ۱۸.۳ الزامات امنیتی (MODULE 34)

| مورد | پیاده‌سازی |
|---|---|
| **Password Hashing** | **Argon2id** (پیش‌فرض Laravel)؛ حداقل طول ۱۲؛ سیاست پیچیدگی بر اساس `zxcvbn` |
| **MFA** | TOTP (RFC 6238)؛ کدهای بازیابی یک‌بارمصرف؛ اجباری برای نقش‌های `Admin`, `HR Admin`, `Finance` |
| **Session Security** | کوکی `HttpOnly` + `Secure` + `SameSite=Lax`؛ چرخش شناسه پس از ورود؛ انقضای مطلق و относитель |
| **CSRF** | توکن دوبل‌سابمیت برای درخواست‌های state-changing (الگو از Management System، اصلاح‌شده برای SPA) |
| **XSS** | فرار خودکار در templating + CSP سخت‌گیرانه + sanitize خروجی‌هایrich-text |
| **SQL Injection** | Eloquent/Query Builder پارامتری؛ **ممنوعیت** SQL خام بدون binding (بررسی در CI) |
| **Rate Limiting** | ورود: ۵ تلاش/دقیقه/IP + قفل تدریجی؛ endpointهای عمومی: سخت‌گیرانه‌تر |
| **Input Validation** | FormRequestها برای هر endpoint؛ whitelist، نه blacklist |
| **Output Encoding** | Resource/Transformer؛ هرگز فیلد خام به خروجی نمی‌رود |
| **HTTPS** | HSTS با `max-age` طولانی؛ هدایت اجباری |
| **File Validation** | بررسی MIME واقعی (magic bytes) + محدودیت اندازه + ذخیره با نام تصادفی خارج از webroot |
| **File Access Control** | URLهای امضاشدهٔ موقت با انقضای کوتاه؛ هر دسترسی در Audit ثبت می‌شود |
| **Secrets** | `.env` خارج از مخزن؛ `.env.example` مستند؛ چرخش منظم |
| **Encryption** | AES-256-GCM در سطح کاربرد برای کد ملی و شماره حساب؛ TLS در انتقال؛ پشتیبان‌ها رمزنگاری‌شده |

### ۱۸.۴ نقشهٔ راه SSO (آینده)

| مرحله | اقدام |
|---|---|
| اکنون | Identity داخل HRM؛ انتشار `POST /api/v1/auth/introspect` برای سرویس‌ها |
| بعداً | Management System به‌جای ساخت User، توکن HRM را اعتبارسنجی می‌کند |
| دورتر | استخراج Identity Context به سرویس مستقل + OIDC؛ HRM و Management System هر دو کلاینت می‌شوند |

> **نکتهٔ معماری:** به همین دلیل `users` در یک **Bounded Context جدا** (`Identity`) قرار می‌گیرد و `employees` به آن `user_id` ارجاع می‌دهد — نه ادغام. این کار استخراج بعدی را بدون migration داده‌ای ممکن می‌کند.

---

## ۱۹. معماری Authorization (MODULE 8)

### ۱۹.۱ مدل

**RBAC پویا با Permissionهای granular** — نقش‌ها در پایگاه داده تعریف می‌شوند، نه در کد.

```
users ──< user_roles >── roles ──< role_permissions >── permissions
                           │
                           └──< role_scopes   (محدوده: all / company / department / team / self)
```

### ۱۹.۲ Permissionها — granular و سلسله‌مراتبی

الگوی نام‌گذاری: `{resource}.{action}` یا `{resource}.{field}.{action}`

```
سازمان
  organization.view · organization.manage
  department.view · department.create · department.edit · department.delete

پرسنل
  employee.view · employee.create · employee.edit · employee.delete
  employee.contact.view
  employee.national_id.view                    ← حساس
  employee.salary.view · employee.salary.edit  ← بسیار حساس
  employee.bank.view · employee.bank.edit      ← بسیار حساس
  employee.document.view · employee.document.upload · employee.document.delete
  employee.lifecycle.transition

زمان
  attendance.view.self · attendance.view.team · attendance.view.all
  attendance.edit.self · attendance.edit.team · attendance.edit.all
  shift.manage · holiday.manage
  leave.request · leave.view.self · leave.view.team · leave.view.all
  leave.approve · leave.reject · leave.type.manage

جذب و استخدام
  recruitment.view · recruitment.manage
  job_opening.publish
  applicant.view · applicant.evaluate · applicant.offer

قرارداد / مدارک
  contract.view · contract.manage
  document.download.self · document.download.all

عملکرد / آموزش / دارایی‌ها
  performance.view.self · performance.view.team · performance.review.manage
  training.manage · asset.manage

درخواست‌ها
  request.create · request.approve · workflow.manage

گزارش / حسابرسی
  report.view · report.export
  audit.view                                   ← فقط Auditor/Admin

پیکربندی
  settings.manage · role.manage · permission.assign
```

### ۱۹.۳ Scope — محدودهٔ دسترسی

Permission به‌تنهایی کافی نیست؛ **محدوده** تعیین می‌کند روی چه داده‌ای:

| Scope | معنا | مثال |
|---|---|---|
| `self` | فقط رکورد خود کاربر | کارمند حضور‌و‌غیاب خود را می‌بیند |
| `team` | اعضای تیمِ تحت سرپرستی (از درخت سازمان) | Team Lead مرخصی اعضا را تأیید می‌کند |
| `department` | کل دپارتمان (و زیردپارتمان‌ها) | Department Manager |
| `company` | کل شرکت | HR Admin |
| `all` | همه‌چیز | Super Admin |

### ۱۹.۴ اصل اِعمال در Backend

> مأموریت: «Permission باید روی Backend enforce شود. صرفاً مخفی کردن Button در Frontend کافی نیست.»

**سه‌لایهٔ اِعمال اجباری:**

| لایه | مکانیزم | شکست = |
|---|---|---|
| ۱. **Routing/Middleware** | بررسی Permission پیش از رسیدن به کنترلر | ۴۰۳ |
| ۲. **Policy (در Service)** | بررسی مالکیت/محدوده روی تک‌رکورد — `Policy::view($user, $employee)` | ۴۰۳ / ۴۰۴ |
| ۳. **Query Scope** | فیلتر خودکار در سطح SQL — کاربر اصلاً رکوردهای غیرمجاز را نمی‌گیرد | مجموعهٔ خالی |

**نمونهٔ جریان:**
```
GET /api/v1/employees
   ↓ Middleware: requires employee.view                       → 403 اگر ندارد
   ↓ Query Scope: WHERE company_id = ? AND (
        id = :self
     OR department_id IN ( subtree(:manager_dept) )   ← اگر scope=team/department
     OR TRUE                                          ← اگر scope=company/all
     )
   ↓ Resource: حذف national_id / salary / bank مگر permission صریح
   ↓ Response: فقط فیلدهای مجاز
```

### ۱۹.۵ نقش‌های پیش‌فرض (Seed — قابل تغییر)

> مأموریت: «Roleها باید Dynamic باشند.» این‌ها فقط نقطهٔ شروع‌اند و در Settings قابل ویرایش.

| نقش | Scope | Permissionهای کلیدی |
|---|---|---|
| **Super Admin** | `all` | همه |
| **HR Admin** | `company` | `employee.*` (شامل salary) · `contract.*` · `leave.*` · `recruitment.*` · `report.*` · `settings.manage` |
| **HR Manager** | `company` | `employee.view/edit` · بدون `salary.edit` · `leave.approve` · `recruitment.*` |
| **Department Manager** | `department` | `employee.view` · `leave.approve` · `attendance.view.team` · `performance.view.team` |
| **Team Lead** | `team` | `employee.view` · `leave.approve` · `attendance.view.team` |
| **Employee** | `self` | `attendance.view.self` · `leave.request` · `employee.view.self` · `request.create` |
| **Finance** | `company` | `employee.salary.view/edit` · `employee.bank.view` · `contract.view` · `report.view` — **بدون** دسترسی به مدارک شخصی یا ارزیابی |
| **Auditor** | `company` | `audit.view` · `report.view` · **فقط خواندن**، بدون دسترسی به دادهٔ پرسنلی |

> ⚠️ **نکتهٔ طراحی:** `Finance` به `national_id` نیاز ندارد مگر برای امور بیمه/مالیات — در آن صورت با permission جداگانه و ثبت در Audit.

### ۱۹.۶ تست‌های اجباری (MODULE 46)

```
✗ Employee A نتواند Employee B را ببیند
✗ Employee A نتواند حقوق Employee B را ببیند
✗ Employee A نتواند مدارک Employee B را ببیند
✗ Employee A نتواند اطلاعات خصوصی Employee B را ببیند
✗ Manager A نتواند دادهٔ Department B را ببیند
✗ Manager A نتواند دادهٔ Department B را ببیند حتی با حدس زدن ID
✗ کاربر بدون employee.salary.view فیلد salary را در پاسخ JSON نبیند
✗ جست‌وجوی سراسری نتواند دادهٔ خارج از scope را افشا کند
✗ دانلود مستقیم فایل بدون نشست معتبر ناموفق باشد
✗ URL امضاشده پس از انقضا ناموفق باشد
```

---

## ۲۰. معماری UI

### ۲۰.۱ مبنای طراحی

> مأموریت: «هیچ Design Language جدیدی که با Brand Repository تضاد دارد ایجاد نکن… خروجی نباید شبیه یک Template آماده باشد.»

**رویکرد:** یک **Admin Design System اختصاصی** که:
- از tokens برند استفاده می‌کند (بخش ۴)
- ساختار اثبات‌شدهٔ Management System را **گسترش** می‌دهد (بخش ۵٫۴)
- وب‌سایت استاتیک را **الگو قرار نمی‌دهد** (رنگ‌ها و فونت آن با برند مغایر است — بخش ۴٫۸)

### ۲۰.۲ خط لولهٔ Design Token

```
brand-ghatehresan/brand-book.html :root
        │  (استخراج یک‌باره + نگهداری دستی با بررسی انحراف)
        ▼
   design-tokens.json        ← منبع واحد حقیقت (Single Source of Truth)
        │  (تولید خودکار)
        ▼
   Tailwind CSS v4 @theme    ← src/theme.css
        ▼
   CSS Custom Properties     ← --color-navy, --color-orange, ...
        ▼
   React Components / Blade  ← مصرف‌کننده
```

### ۲۰.۳ توکن‌های HRM (برگرفته از برند + گسترش برای ابزار داخلی)

| دسته | توکن | مقدار | منبع |
|---|---|---|---|
| رنگ اصلی | `--color-navy` | `#0E2A47` | برند |
| رنگ اصلی hover | `--color-navy-2` | `#16375A` | Management System |
| تأکید | `--color-orange` | `#F26B1D` | برند |
| تأکید hover | `--color-orange-d` | `#D25510` | Management System |
| متن | `--color-ink` | `#111820` | برند |
| متن فرعی | `--color-steel` | `#55616E` | برند |
| خط | `--color-line` | `#E8EBEF` | برند |
| پس‌زمینه | `--color-paper` | `#F9FAFB` | برند |
| موفقیت | `--color-success` | `#1F9D55` | برند |
| خطا | `--color-danger` | `#D93025` | برند |
| هشدار | `--color-warning` | `#F0A202` | برند |
| اطلاع | `--color-info` | `#2563EB` | برند |
| سایدبار | `--sidebar-w` | `252px` | Management System |
| شعاع | `--radius` | `12px` | Management System |
| سایه | `--shadow` | `0 1px 3px rgba(14,42,71,.07), 0 4px 14px rgba(14,42,71,.05)` | Management System |
| فونت فارسی | `--font-fa` | `Vazirmatn` (self-hosted) | برند |
| فونت لاتین | `--font-en` | `Inter` (self-hosted) | برند |
| اندازهٔ بدنهٔ ابزار | — | `14.5px / 1.85` | Management System (مناسب تراکم ابزار داخلی) |
| Breakpoint موبایل | — | `900px` | Management System |

> ⚠️ **توجه به تراکم:** برند بدنه را `16.5px/1.9` تعیین کرده، اما Management System برای ابزار داخلی از `14.5px/1.85` استفاده می‌کند. برای HRM (یک ابزار داخلی با جداول متراکم) **تراکم Management System** را پیشنهاد می‌کنم، با حفظ `16.5px` در صفحات محتوایی (مثل Self-Service پروفایل). این یک انطباق مستند است، نه تخطی.

### ۲۰.۴ کتابخانهٔ کامپوننت (MODULE 41)

| کامپوننت | وضعیت در Management System | برای HRM |
|---|---|---|
| Sidebar | ✅ موجود (`--side:252px`، navy، آیتم فعال نارنجی) | reuse + گسترش (زیردرخت، جست‌وجو) |
| Header / Page Head | ✅ `.phead` | reuse |
| Card | ✅ `.card/.card-h/.card-b` | reuse |
| Table | ✅ `.tw/.tight/.num` | reuse + مرتب‌سازی/فیلتر/صفحه‌بندی |
| Badge (وضعیت) | ✅ `.badge .b-*` — ۵px radius, 12px bold | reuse دقیقاً طبق برند |
| Button | ✅ `.btn/.btn-p/.btn-n/.btn-d/.btn-sm` | reuse |
| Form Field | ✅ `.frm/.fld/.row/.hint` | reuse + validation states |
| Stats / KPI | ✅ `.stats/.stat/.lb/.vl` | reuse |
| Empty State | ✅ `.empty/.ei` | reuse + گسترش (action slot) |
| Flash / Alert | ✅ `.flash .f-*` • `.note .n-*` | reuse |
| Pagination | ✅ `.pager` | reuse + cursor-based |
| Progress / Bar | ✅ `.pbar/.bars` | reuse |
| Tabs | ✅ `.tabs` | reuse |
| Filters | ✅ `.filters/.chips/.chip` | reuse + گسترش |
| **Modal** | ❌ **وجود ندارد** | **جدید** — ساخته می‌شود |
| **Drawer** | ❌ **وجود ندارد** | **جدید** |
| **Date Picker (Jalali)** | ❌ **وجود ندارد** (فقط `<input type="date">` میلادی) | **جدید — حیاتی** |
| **File Uploader** | ❌ **وجود ندارد** (هیچ آپلودی در سیستم نیست) | **جدید** |
| **Notification Center** | ❌ وجود ندارد | **جدید** |
| **Kanban / Pipeline** | ❌ وجود ندارد | **جدید** (برای ATS) |
| **Calendar** | ❌ وجود ندارد | **جدید** (MODULE 25) |
| **Combobox / Search** | ❌ وجود ندارد | **جدید** (MODULE 22) |
| **Avatar** | ❌ وجود ندارد (فقط تصویر در Website mockup) | **جدید** |
| **Loading / Skeleton** | ⚠️ در Website موجود (`.shimmer-effect`) | reuse از Website (فقط CSS) |

### ۲۰.۵ الزامات RTL / فارسی / Jalali (MODULE 27)

| مورد | تصمیم |
|---|---|
| جهت | `<html lang="fa" dir="rtl">` |
| ویژگی‌های منطقی CSS | `margin-inline-start` به‌جای `margin-left`؛ `padding-inline` به‌جای `padding-left/right` |
| سایدبار | سمت **راست** (مطابق Management System: `right:0` و `margin-right`) |
| اعداد در UI | **فارسی** (مبالغ، تاریخ، شمارش‌ها) |
| کدها / شناسه‌ها | **لاتین** (`EMP-0142`, `GR-48213`) — مطابق برند |
| ورودی عددی | نرمال‌سازی خودکار ارقام فارسی/عربی → لاتین (الگوی `app.js:22-39`) |
| تقویم | **Jalali** در UI؛ ذخیرهٔ میلادی UTC در Backend |
| Date Picker | تقویم شمسی با ماه‌های فارسی (`JMONTHS` از `helpers.php:99`) |
| نیم‌فاصله | ZWNJ در «قطعه‌رسان»، «می‌رسونیم» |
| فونت | Vazirmatn self-hosted (۴۰۰/۵۰۰/۷۰۰/۹۰۰) + Inter — **بدون CDN خارجی** |

### ۲۰.۶ Accessibility (MODULE 44)

| الزام | تحقق |
|---|---|
| کنتراست | استفاده از نسبت‌های تأیید‌شدهٔ برند (بخش ۴٫۲)؛ خطای متداول «نارنجی روی سفید برای متن ریز» ممنوع |
| ناوبری کیبورد | تمام کامپوننت‌های تعاملی focus-visible؛ ترتیب tab منطقی |
| برچسب | هر فیلد دارای `<label for>` واقعی (نه فقط placeholder) |
| ARIA | نقش‌های مناسب برای Modal/Drawer/Tabs/Table؛ `aria-live` برای اعلان‌ها و خطاها |
| HTML معنایی | `<nav>`, `<main>`, `<table>` با `<th scope>` |
| Screen Reader | متن جایگزین برای آیکون‌ها؛ اعلان وضعیت برای عملیات async |
| تراکم/اندازه | حداقل ۱۴px برای متن بدنه؛ اهداف لمسی ≥ ۴۴px در موبایل |

### ۲۰.۷ Responsive (MODULE 43)

| سطح | رفتار |
|---|---|
| Desktop (≥900px) | سایدبار ثابت ۲۵۲px + محتوا |
| Tablet (<900px) | سایدبار → Drawer با overlay (الگوی `.open` از Management System) |
| Mobile | نوار بالای چسبان + ناوبری پایین؛ جداول → کارت‌های انباشته |

**قابلیت‌های موبایل در فاز اول:** مشاهدهٔ پروفایل · درخواست مرخصی · ثبت/مشاهدهٔ حضور‌و‌غیاب · اعلان‌ها · تأیید درخواست‌ها (برای Manager)

### ۲۰.۸ داشبورد بر اساس نقش (MODULE 23)

| نقش | محتوای داشبورد |
|---|---|
| **Employee** | حضور‌و‌غیاب من · مرخصی من · درخواست‌های من · مدارک من · اعلان‌ها |
| **Manager** | اعضای تیم · درخواست‌های در انتظار تأیید · حضور‌و‌غیاب تیم · مرخصی تیم · آمار تیم |
| **HR** | کل پرسنل · استخدام · قراردادها · مرخصی‌های در انتظار · گزارش‌ها · انقضاها |
| **Admin** | همه‌چیز + سلامت سیستم + حسابرسی |

---

## ۲۱. نقشهٔ راه ماژول‌ها (Module Roadmap)

۲۴ ماژول در ۱۲ Milestone — منطبق بر Milestoneهای مأموریت.

| M# | Milestone | ماژول‌ها | خروجی قابل تحویل |
|---|---|---|---|
| **M0** | Repository Audit | — | ✅ **این سند** |
| **M1** | Architecture & Design System | M41 (پایه) · M27 (Jalali) · M33/M34 (طرح) | `ARCHITECTURE.md` · `DATABASE.md` · `SECURITY.md` · `ENVIRONMENT.md` · بستهٔ tokens · کتابخانهٔ کامپوننت پایه |
| **M2** | Authentication + Authorization | M8 · M20 · M33 · M34 · M35 (بخشی) | `AUTHENTICATION.md` · `AUTHORIZATION.md` · Login/Logout · MFA · RBAC · Audit Log · تست‌های امنیتی |
| **M3** | Organization | M1 · M28 (زیرساخت) | شرکت · شعبه · دپارتمان (درخت پویا) · تیم · سمت · پوزیشن · مدیر |
| **M4** | Employees | M2 · M3 · M36 | پروفایل کامل پرسنل · چرخهٔ عمر · مخاطب · استخدام · فیلدهای حساس رمزنگاری‌شده |
| **M5** | Employee Self Service | M9 · M19 · M23 | پروفایل خود · حضور‌و‌غیاب خود · مرخصی خود · مدارک خود · اعلان‌ها |
| **M6** | Attendance | M5 · M6 | ورود/خروج · شیفت · قالب شیفت · تخصیص · اضافه‌کاری · تأخیر · غیاب |
| **M7** | Leave | M7 · M26 | انواع مرخصی · درخواست · گردش تأیید · مانده · تعطیلات · تقویم (M25) |
| **M8** | Documents | M14 · M37 · M13 | آپلود/دانلود امن · نسخه‌بندی · انقضا · قراردادها · کنترل دسترسی |
| **M9** | Recruitment | M4 · M32 · M31 (قرارداد) | آگهی شغلی · متقاضی · درخواست · پایپ‌لاین قابل تنظیم · مصاحبه · ارزیابی · پیشنهاد |
| **M10** | Requests + Workflow | M18 · M10 · M40 | موتور گردش‌کار · انواع درخواست · تأیید چندمرحله‌ای · صف پس‌زمینه |
| **M11** | Reports | M21 · M22 · M11 · M16 · M15 · M17 | گزارش‌ها · خروجی Excel/CSV/PDF · جست‌وجوی سراسری با Permission-filter · عملکرد · آموزش · دارایی‌ها |
| **M12** | Integrations | M30 · M31 · M12 · M38 · M39 · M47 · M49 · M50 | `INTEGRATION.md` · `API.md` · OpenAPI · معماری Payroll · پشتیبان‌گیری · لاگینگ · استقرار · CI/CD |

**قانون پس از هر Milestone (طبق مأموریت):**
```
1. Test (Unit + Feature + Security)
2. Lint (PHP-CS-Fixer / Pint + ESLint)
3. Type check (PHPStan سطح 8 + TypeScript strict)
4. Build (Vite production build)
5. Security check (composer audit · npm audit · بررسی دستی دسترسی‌ها)
6. Git diff (بازبینی)
7. Documentation update
```

**خروجی‌های Milestone 0 (این مرحله):**
- ✅ `AUDIT.md` — این سند
- ⏳ منتظر تأیید: معماری پیشنهادی، Stack، و تصمیمات بخش ۲۵

---

## ۲۲. معماری استقرار (MODULE 49/50)

### ۲۲.۱ محیط‌ها

> ✅ **تصمیم ثبت‌شده (۱۴۰۵/۰۷/۰۲):** توسعه روی **XAMPP لوکال**؛ استقرار Production بعداً روی **هاست اشتراکی یا VPS**.
> این تصمیم در بخش ۲۲٫۵ به الزامات فنی ترجمه شده است.

| محیط | هدف | پلتفرم | دیتابیس | ویژگی‌ها |
|---|---|---|---|---|
| **development** | توسعهٔ محلی | **XAMPP (ویندوز)** — Apache + PHP 8.3 + MariaDB | MariaDB محلی | debug روشن · دادهٔ Seed **ساختگی** · Mail روی `log` · Queue روی `sync`/`database` · HTTPS خودامضا یا HTTP |
| **staging** | پیش‌تولید | هاست اشتراکی یا VPS | MySQL/MariaDB ایزوله | آینهٔ production · HTTPS · دادهٔ **ساختگی** · بررسی مهاجرت پیش از Production |
| **production** | واقعی | **هاست اشتراکی یا VPS** (بعداً تعیین می‌شود) | MySQL/MariaDB با پشتیبان | HTTPS · کوکی امن · لاگینگ · مانیتورینگ · پشتیبان رمزنگاری‌شده **خارج از سرور** |

### ۲۲.۲ توپولوژی Production

```
                اینترنت
                    │
                    ▼
        ┌───────────────────────┐
        │   Reverse Proxy       │  TLS 1.3 · HSTS · هدایت HTTP→HTTPS
        │   Nginx               │  هدرهای امنیتی · Rate Limit لبه
        └───────────┬───────────┘
                    │
        ┌───────────▼───────────┐
        │   PHP-FPM 8.3         │
        │   Laravel 12          │
        │   ├── /api/v1         │
        │   └── Web UI          │
        └───┬───────────┬───────┘
            │           │
   ┌────────▼──┐   ┌────▼──────────┐
   │ MySQL 8   │   │ Redis         │  Cache · Session · Queue
   │ utf8mb4   │   └───────────────┘
   │ persian_ci│
   └───────────┘
            │
   ┌────────▼──────────────────────────────┐
   │ Private Storage  (خارج از webroot)    │
   │ /var/www/hrm/storage/app/private/     │
   │  ├── documents/   (مدارک پرسنلی)      │
   │  └── contracts/   (قراردادها)         │
   │ دسترسی فقط از طریق URL امضاشده        │
   └───────────────────────────────────────┘

   ┌───────────────────────────────────────┐
   │ پشتیبان (خارج از سرور — الزامی M38)   │
   │ DB (رمزنگاری‌شده) + فایل‌ها           │
   │ نگهداری: روزانه ۱۴ · هفتگی ۸ · ماهانه ۱۲ │
   └───────────────────────────────────────┘
```

### ۲۲.۵ الزامات خاص XAMPP / هاست اشتراکی

از آنجا که توسعه روی **XAMPP** و استقرار احتمالی روی **هاست اشتراکی** انجام می‌شود، محدودیت‌های زیر **الزام معماری** هستند (نه توصیه):

| # | محدودیت | علت | الزام معماری |
|---|---|---|---|
| **X-1** | **Redis در دسترس نیست** | XAMPP بستهٔ Redis ندارد؛ اکثر هاست‌های اشتراکی هم ندارند | Cache و Session و Queue باید با درایور **`database`** کار کنند. Redis فقط یک بهینه‌سازی اختیاری است که در صورت VPS فعال می‌شود — **هیچ کدی نباید به Redis وابسته باشد** |
| **X-2** | **Queue Worker بلندمدت در دسترس نیست** | روی هاست اشتراکی نمی‌توان `php artisan queue:work` را اجرا کرد | زمان‌بند باید **دو حالته** باشد: (۱) Cron در صورت دسترسی؛ (۲) **مسیر HTTP محافظت‌شده با توکن** (`/schedule/run?token=…`) که توسط یک سرویس cron خارجی فراخوانی می‌شود. Jobها باید **idempotent** باشند |
| **X-3** | **دسترسی CLI محدود** | برخی هاست‌های اشتراکی SSH نمی‌دهند | هر عملیات ضروری باید یک **مسیر HTTP معادل** داشته باشد (مثلاً `POST /admin/migrate` محافظت‌شده) یا از طریق یک اسکریپت وب اجرا شود. مستندات استقرار هر دو مسیر را پوشش می‌دهد |
| **X-4** | **فایل‌های خصوصی باید خارج از `public_html` باشند** | روی هاست اشتراکی معمولاً فقط `public_html` در معرض وب است | ساختار پوشه باید طوری باشد که **تنها** پوشهٔ `public/` داخل `public_html` قرار گیرد؛ بقیهٔ Laravel بیرون آن. در XAMPP هم همین الگو (`htdocs/hrm/public`) رعایت می‌شود تا انتقال بدون تغییر باشد |
| **X-5** | **نسخهٔ PHP روی هاست متفاوت است** | هاست‌های اشتراکی ممکن است PHP 8.1/8.2 داشته باشند | حداقل نسخهٔ پشتیبانی‌شده **PHP 8.2** تعیین می‌شود؛ بررسی نسخه در CI و در یک Health Check |
| **X-6** | **Node در Production لازم نیست** | دارایی‌های Frontend باید از پیش ساخته شوند | خروجی Vite در مخزن commit نمی‌شود؛ در CI ساخته و همراه استقرار منتقل می‌شود. در محیط توسعهٔ XAMPP ابتدا `npm run build` اجرا می‌شود |
| **X-7** | **SQLite برای Production مناسب نیست** | قفل‌گذاری و نبود کاربر/دسترسی | MySQL/MariaDB روی **هر سه** محیط — یکسان‌سازی رفتار و جلوگیری از غافلگیری هنگام انتقال |
| **X-8** | **پشتیبان روی همان سرور کافی نیست** | الزام mأموریت M38 | پشتیبان باید به مقصدی **خارج از سرور** منتقل شود. روی هاست اشتراکی: اسکریپت زمان‌بند + انتقال به فضای ابری/SSH remote |

**ساختار پوشهٔ استاندارد (یکسان در XAMPP و Production):**

```
XAMPP:                          Production (هاست اشتراکی):
C:\xampp\htdocs\                ~/                       ← خارج از public_html
└── hrm/                        └── hrm/
    ├── app/                        ├── app/
    ├── storage/                    ├── storage/
    │   └── app/private/            │   └── app/private/  ← مدارک پرسنلی
    ├── .env                        ├── .env              ← خارج از دسترس وب
    └── public/  ← DocumentRoot     └── public_html/  ← DocumentRoot
        └── index.php                   └── index.php
```

> ⚠️ **نکتهٔ امنیتی حیاتی برای XAMPP:** در پیش‌فرض XAMPP همه‌چیز زیر `htdocs` از طریق وب قابل دسترسی است.
> الزام: DocumentRoot باید دقیقاً روی `hrm/public/` تنظیم شود (از طریق `httpd-vhosts.conf`)،
> یا از `php artisan serve` استفاده شود. در غیر این صورت `.env` و `storage/` در معرض دسترسی مستقیم خواهند بود.
> این مورد در `DEPLOYMENT.md` با دستور دقیق برای XAMPP ویندوز مستند خواهد شد.

### ۲۲.۳ CI/CD (GitHub Actions)

```
Push به feature/*  → Lint · Type check · Unit tests
PR به develop      → + Feature/Integration tests · Build · Security audit
Push به main       → + E2E (Playwright) · Deploy به staging
Tag v*             → Deploy به production (تأیید دستی)
```

**بررسی‌های اجباری در CI:**
- `pint --test` / `eslint`
- `phpstan --level=8`
- `tsc --noEmit` (strict)
- `php artisan test` (Unit + Feature + **تست‌های Permission طبق M46**)
- `playwright test` (E2E: ورود · ایجاد کارمند · درخواست مرخصی · تأیید مرخصی · آپلود مدرک · بررسی دسترسی)
- `composer audit` + `npm audit`
- بررسی عدم وجود راز در diff

### ۲۲.۴ الزامات Production

| مورد | الزام |
|---|---|
| HTTPS | اجباری + HSTS |
| کوکی | `Secure` · `HttpOnly` · `SameSite=Lax` |
| دیتابیس | کاربر DB با کمترین دسترسی؛ اتصال فقط از localhost/شبکهٔ داخلی |
| پشتیبان | خودکار، رمزنگاری‌شده، **خارج از سرور** (الزام M38)، تست بازیابی ماهانه |
| لاگینگ | درخواست‌ها · خطاها · رویدادهای امنیتی · کارهای پس‌زمینه — **بدون** دادهٔ حساس (M39) |
| مانیتورینگ | سلامت · زمان پاسخ · نرخ خطا · فضای دیسک · وضعیت Queue |
| رازها | `.env` خارج از مخزن · مدیریت‌شده توسط سیستم استقرار |

---

## ۲۳. اطلاعات ناکافی (UNKNOWN)

مواردی که از Source Code قابل استخراج نبود و نیازمند ورودی شماست:

| # | موضوع | UNKNOWN | تأثیر بر معماری |
|---|---|---|---|
| U-1 | ~~**زیرساخت استقرار**~~ | ✅ **پاسخ داده‌شده (۱۴۰۵/۰۷/۰۲):** توسعه روی XAMPP لوکال · Production بعداً روی هاست اشتراکی یا VPS | → PostgreSQL **کنار گذاشته شد** · درایور database برای Cache/Queue · زمان‌بند HTTP محافظت‌شده · جزئیات در ۲۲٫۵ |
| U-2 | **تعداد واقعی پرسنل** | مقیاس هدف (۱۰؟ ۵۰؟ ۵۰۰؟) | تصمیمات ایندکس، صفحه‌بندی، کش گزارش‌ها |
| U-3 | **تعداد شعب/شرکت‌ها** | فقط یک شرکت؟ یا برنامهٔ گسترش؟ | فعال‌سازی Tenant Isolation در Milestone 3 (M28) |
| U-4 | **ساختار سازمانی واقعی** | دپارتمان‌های فهرست‌شده در مأموریت (مدیریت/فروش/مالی/منابع انسانی/انبار/فنی/پشتیبانی) تأیید‌شده؟ | Seed اولیهٔ Organization |
| U-5 | **دستگاه حضور‌و‌غیاب** | آیا دستگاهی موجود است یا در آینده خریداری می‌شود؟ برند/مدل؟ | طراحی رابط integration در M6 |
| U-6 | **قوانین مرخصی و کار** | سقف مرخصی سالانه · ساعات کاری · روزهای تعطیل رسمی · شیفت‌ها | Seed تنظیمات در M7 |
| U-7 | **واحد پول مبالغ** | مبالغ Management System به ریال است یا تومان؟ (`buy_price`/`sell_price` بدون واحد) | `DECIMAL` + ستون `currency`؛ جلوگیری از خطای ۱۰× در Payroll |
| U-8 | **ترجیح تیم توسعه** | مهارت تیم در Laravel / React؟ | انتخاب بین Inertia+React و Blade+Alpine |
| U-9 | **سرویس پیامک/ایمیل** | کدام ارائه‌دهنده؟ (SMTP/پنل پیامک ایرانی) | ماژول Notification در M19 |
| U-10 | **الزامات قانونی/بیمه** | آیا نیاز به گزارش‌گیری بیمه/مالیات در فاز اول هست؟ | اولویت‌بندی Payroll (M12) |
| U-11 | **سیستم حسابداری موجود** | آیا نرم‌افزار حسابداری/حقوق‌دستمزد دیگری استفاده می‌شود؟ | معماری Payroll و integration |
| U-12 | **دامنه و هاست Website** | آیا `ghatehresan-website` قرار است واقعاً مستقر شود؟ روی چه دامنه‌ای؟ | طراحی یکپارچه‌سازی Careers (M31/32) |
| U-13 | **مالکیت و تیم نگهداری** | چه کسی HRM را نگه‌داری می‌کند؟ | سطح پیچیدگی قابل قبول |
| U-14 | **نرخ رشد و بازهٔ زمانی** | زمان مورد انتظار برای راه‌اندازی؟ | ترتیب و وسعت Milestoneها |

---

## ۲۴. ماتریس Integration (تکمیل‌شده با وضعیت واقعی)

Legend: ✅ پیاده‌سازی‌شده · 🔶 تا حدی / Mockup · ❌ وجود ندارد · ⬜ برنامه‌ریزی‌شده

| Feature | HRM | Management System | Website (`ghatehresan-website`) | Source of Truth |
|---|---|---|---|---|
| **Users / Identity** | ⬜ برنامه‌ریزی‌شده | ❌ ندارد | 🔶 فقط Mockup HTML | **HRM** (آینده: Identity Service مستقل) |
| **Roles / Permissions** | ⬜ برنامه‌ریزی‌شده | ❌ ندارد (فقط `suppliers.role`) | 🔶 فقط Mockup (admin/author/user) | **HRM** |
| **Authentication** | ⬜ برنامه‌ریزی‌شده | ❌ ندارد | ❌ ندارد (Mock سمت کلاینت) | **HRM** |
| **Employees** | ⬜ برنامه‌ریزی‌شده | ❌ ندارد | ❌ ندارد | **HRM** |
| **Organization (Dept/Position)** | ⬜ برنامه‌ریزی‌شده | ❌ ندارد | ❌ ندارد | **HRM** |
| **Customers** | ❌ نیاز ندارد | ✅ دارد (`customers`) | 🔶 Mockup (`admin-panel-*`) | **Management System** |
| **Products** | ❌ نیاز ندارد | ✅ دارد (`products`) | 🔶 تصاویر استاتیک | **Management System** |
| **Orders** | ❌ نیاز ندارد | ✅ دارد (`orders`) | 🔶 Mockup | **Management System** |
| **Suppliers** | ❌ نیاز ندارد | ✅ دارد (`suppliers`) | ❌ ندارد | **Management System** |
| **Stock / Inventory** | ❌ نیاز ندارد | ✅ دارد (`stock_moves`) | ❌ ندارد | **Management System** |
| **Job Openings** | ⬜ برنامه‌ریزی‌شده | ❌ ندارد | ❌ ندارد | **HRM** (انتشار عمومی) |
| **Applications / CV** | ⬜ برنامه‌ریزی‌شده | ❌ ندارد | ❌ ندارد | **HRM** (خصوصی) |
| **Careers Page** | ⬜ تولید API عمومی | ❌ ندارد | ⬜ باید ساخته شود | **HRM** (داده) · Website (نمایش) |
| **Attendance** | ⬜ برنامه‌ریزی‌شده | ❌ ندارد | ❌ ندارد | **HRM** |
| **Leave** | ⬜ برنامه‌ریزی‌شده | ❌ ندارد | ❌ ندارد | **HRM** |
| **Contracts** | ⬜ برنامه‌ریزی‌شده | ❌ ندارد | ❌ ندارد | **HRM** |
| **Documents / Files** | ⬜ برنامه‌ریزی‌شده | ❌ **هیچ آپلودی ندارد** | 🔶 تصاویر استاتیک | **HRM** |
| **Notifications** | ⬜ برنامه‌ریزی‌شده | ❌ ندارد | 🔶 Mockup | **HRM** |
| **Audit Log** | ⬜ برنامه‌ریزی‌شده | ❌ ندارد | 🔶 Mockup (`admin-panel-logs`) | **HRM** |
| **Reports** | ⬜ برنامه‌ریزی‌شده | ✅ دارد (`reports`, `export` CSV) | 🔶 Mockup | هر کدام در دامنهٔ خود |
| **API** | ⬜ `/api/v1` | ❌ ندارد (فقط CSV) | ❌ ندارد | — |
| **Brand Identity** | ⬜ مصرف‌کننده | ⚠️ منطبق با برند | ❌ **مغایر با برند** | **Brand Repository** |
| **Design Tokens** | ⬜ تولید از برند | ✅ CSS `:root` (منطبق) | ⚠️ Tailwind (مغایر) | **Brand Repository** |

### ۲۴.۱ نتیجه: چه چیزی مشترک است؟

| مورد | تصمیم |
|---|---|
| **User** | مالکیت با **HRM**. Management System در آینده reference می‌دهد، کپی نمی‌کند. |
| **Employee** | مالکیت با **HRM**. در Management System وجود ندارد که کپی شود. |
| **Organization** | مالکیت با **HRM** (SoT). Management System در آینده فقط می‌خواند. |
| **Notifications** | مالکیت با **HRM** برای رویدادهای HR. رویدادهای فروش/انبار در Management System می‌مانند. |
| **Files** | مالکیت با **HRM** برای مدارک پرسنلی. فایل‌های محصول در Management System می‌مانند. |
| **Customers/Products/Orders** | مالکیت با **Management System**. **HRM آن‌ها را کپی نمی‌کند.** |

### ۲۴.۲ الگوی ارتباط

| مسیر | روش | احراز هویت |
|---|---|---|
| Website → HRM (ارسال رزومه) | `POST /api/v1/public/careers/applications` | ناشناس + Rate Limit + Captcha |
| Website → HRM (نمایش آگهی‌ها) | `GET /api/v1/public/careers/job-openings` | ناشناس + Rate Limit |
| Management System → HRM (دریافت دایرکتوری) | `GET /api/v1/integration/directory/employees` | Service Token + IP Allowlist |
| Management System → HRM (اعتبارسنجی توکن) | `POST /api/v1/auth/introspect` | Service Token |
| HRM → Management System | ⬜ تعریف نشده (نیازی در فاز اول نیست) | — |

> **اصل:** هیچ اشتراک مستقیم پایگاه داده‌ای. هر ارتباط از طریق API با Service Token.

---

## ۲۵. تصمیمات — وضعیت

### ✅ ۲۵.۱ تصمیمات ثبت‌شده

| # | تصمیم | پاسخ | تاریخ | پیامد اِعمال‌شده |
|---|---|---|---|---|
| **D-3** | زیرساخت استقرار | **توسعه: XAMPP لوکال · Production: بعداً هاست اشتراکی یا VPS** | ۱۴۰۵/۰۷/۰۲ | PostgreSQL حذف شد · درایور `database` برای Cache/Queue · زمان‌بند HTTP محافظت‌شده · بخش ۲۲٫۵ اضافه شد |

### ⬜ ۲۵.۲ تصمیمات باز — پیش از آغاز Milestone 1

| # | تصمیم | گزینهٔ پیشنهادی من | جایگزین |
|---|---|---|---|
| **D-1** | **Stack فنی** | **PHP 8.3 + Laravel 12 + MySQL/MariaDB** — حالا با پاسخ XAMPP عملاً قطعی است: تنها گزینه‌ای است که روی XAMPP اجرا می‌شود و با PHP موجود تیم هم‌خوان است | Node/NestJS · Django (هر دو روی XAMPP نیازمند نصب جداگانه‌اند) |
| **D-2** | **لایهٔ UI** | **Blade + Alpine.js** — با توجه به XAMPP و مهارت فعلی تیم، بدون نیاز به Node در محیط توسعه؛ در صورت تمایل بعداً به Inertia/React ارتقا می‌یابد بدون بازنویسی Backend | Inertia 2 + React 19 + TypeScript (نیازمند Node در توسعه، UI غنی‌تر) |
| **D-4** | **مقیاس هدف** (U-2/U-3) | ⬜ نیازمند پاسخ شما | <۵۰ پرسنل · ۵۰–۵۰۰ · >۵۰۰ |

**سؤالات تکمیلی (اختیاری اما مفید):**
- آیا در حال حاضر دستگاه حضور‌و‌غیاب دارید یا خیر؟ برند/مدل؟
- تعداد و نام دپارتمان‌های واقعی چیست؟ (برای Seed)
- مبالغ در Management System به ریال است یا تومان؟
- آیا Website قرار است واقعاً مستقر شود؟
- ورژن PHP در XAMPP شما چیست؟ (برای تعیین حداقل نسخهٔ پشتیبانی‌شده)

---

## پیوست الف — منابع و شواهد

| ادعا | منبع دقیق |
|---|---|
| نبود Authentication | `ghatehresan-management-system/app/helpers.php:149-167` (session فقط برای CSRF) · بررسی ۱۱ جدول با `PRAGMA table_info` |
| نبود جدول users | خروجی `SELECT name FROM sqlite_master WHERE type='table'` |
| نبود API | `grep -rniE "json\|api\|fetch(" ` → فقط CSV و دانلود پشتیبان |
| دانلود پشتیبان بدون احراز هویت | `app/pages/settings.php:68-79` |
| حذف داده‌ها بدون احراز هویت | `app/pages/settings.php:55-63` |
| ذخیرهٔ زمان محلی | `app/db.php:91` — `$NOW = "(datetime('now','localtime'))"` |
| Design Tokens برند | `brand-ghatehresan/brand-book.html` → بلوک `:root` |
| مقادیر رنگ | `brand-ghatehresan/zuv-vpy-qzux.txt` (رنگ‌های برند) |
| مقیاس تایپوگرافی | `brand-ghatehresan/brand-book.html` §۰۶ |
| Website = قالب آرینو | `ghatehresan-website/public/login.html:9` — `قالب فرشگاهی آرینو` |
| تضاد رنگ Website | `ghatehresan-website/src/input.css` → `--color-primary:#FF8229FF` |
| تضاد فونت Website | `ghatehresan-website/src/input.css` → `@font-face { font-family:"payda" }` |
| Mockup User/Role/Log | `admin-panel-users-list.html` · `admin-panel-user-roles.html` · `admin-panel-logs.html` |
| `website` خالی است | `ghatehresan/website` → تنها `README.md` (۱۰ بایت)، ۱ commit |
| تعداد رکوردهای DB | `SELECT COUNT(*)` روی هر جدول → categories=9, vehicles=12, مابقی=0 |

## پیوست ب — فهرست فایل‌های خروجی مرحلهٔ بعد

پس از تأیید معماری ایجاد خواهند شد:

```
README.md
ARCHITECTURE.md
DATABASE.md
API.md
AUTHENTICATION.md
AUTHORIZATION.md
SECURITY.md
INTEGRATION.md
DEPLOYMENT.md
ENVIRONMENT.md
BACKUP.md
BRAND.md            (افزوده‌شده: نگاشت tokens برند به Design System)
TESTING.md          (افزوده‌شده: استراتژی تست شامل تست‌های Permission)
```

---

---

## ۲۶. تاریخچهٔ بازنگری سند

| تاریخ | تغییر |
|---|---|
| ۱۴۰۵/۰۷/۰۲ | انتشار اولیه — Audit کامل چهار Repository |
| ۱۴۰۵/۰۷/۰۲ | ثبت تصمیم استقرار (XAMPP → هاست/VPS) · حذف PostgreSQL · افزودن بخش ۲۲٫۵ (الزامات XAMPP/هاست اشتراکی) · به‌روزشده ۱۵٫۲ و ۲۲٫۱ و ۲۳ و ۲۵ |

---

**پایان Milestone 0 — Repository Audit**
**وضعیت: منتظر تأیید تصمیمات باز (D-1 · D-2 · D-4)**
