# DATABASE — قرارداد پایگاه داده HRM

**Milestone 1** · موتور: MySQL 8.0 / MariaDB 10.6+ · Collation: `utf8mb4_persian_ci`
این سند **قرارداد** است: Milestoneهای ۲ تا ۱۲ دقیقاً همین Schema را پیاده می‌کنند.
هر انحرافی نیازمند به‌روزرسانی این سند + migration جدید است (هرگز ویرایش migration اجراشده).

---

## ۱. قراردادهای سراسری

| موضوع | قرارداد |
|---|---|
| Engine/Collation | InnoDB · `utf8mb4_persian_ci` (پیش‌فرض DB + هر جدول) |
| کلید اصلی | `id` از نوع `BIGINT UNSIGNED AUTO_INCREMENT` (`$table->id()`) |
| کلید خارجی | `{table_singular}_id` + ایندکس خودکار؛ نام قیدها پیش‌فرض Laravel |
| حذف | Soft Delete (`deleted_at`) روی همهٔ موجودیت‌های حساس؛ pivotها و توکن‌ها hard |
| تاریخ مدنی | `DATE` (تولد، استخدام، شروع/پایان قرارداد) — بدون timezone |
| رویداد | `DATETIME` به **UTC** (ورود/خروج، ثبت، تصمیم) |
| مبالغ | `DECIMAL(15,2)` + ستون `currency CHAR(3)` با پیش‌فرض `'IRR'` |
| دادهٔ رمزنگاری‌شده | ستون `TEXT` + cast رمزنگاری Laravel (کد ملی، حساب بانکی) |
| JSON | فقط برای دادهٔ نیمه‌ساخت‌یافته (ترجیحات، تعریف فرم) — هرگز برای روابط |
| Audit | جدول `audit_logs` فقط افزودنی: بدون `updated_at`، بدون حذف |
| نام‌گذاری | جداول جمع انگلیسی snake_case؛ ستون‌ها snake_case؛ enumها رشته‌ای (خوانا در DB) |
| Migration | یک migration به‌ازای هر تغییر؛ هرگز ویرایش migration اجراشده؛ `down()` کامل |

### قوانین Cascade

| حالت | قانون | مثال |
|---|---|---|
| موجودیت HR حساس | `restrict` (حذف والد با فرزند ممنوع) | employees ← contracts/documents/attendance |
| فرزند خالص | `cascade` (با حذف والد پاک می‌شود) | workflow_steps ← workflow_definitions |
| ارجاع اختیاری | `nullOnDelete` | documents.uploaded_by ← users |
| Pivot | `cascade` هر دو سمت | role_user، permission_role |
| Audit | **بدون FK** به موجودیت‌ها (morph بدون قید) | audit_logs restorations را نمی‌شکند |

### ترتیب مهاجرت‌ها (وابستگی FK)

```
M2: roles → permissions → users(ALTER) → role_user → permission_role
    → mfa_methods → personal_access_tokens → audit_logs
    فایل‌ها: `2026_09_24_000001` تا `2026_09_24_000008` (دقیقاً به همان ترتیب بالا).
M3: companies → branches → departments → positions → teams
    (ستون‌های manager/lead در M3 ساخته می‌شوند اما FK آن‌ها در M4 اضافه می‌شود)
M4: employees → employee_contacts → employee_bank_accounts → compensations
    + ALTER branches/departments/teams برای FK مدیر/سرپرست
M6: work_schedules → shift_templates → shifts → attendances
M7: leave_types → leave_balances → leave_requests → holidays
M8: document_types → documents → document_versions → contract_types → contracts
M9: job_openings → pipeline_stages → applicants → applications
    → interviews → interview_interviewers → evaluations → offers
M10: request_types → requests → workflow_definitions → workflow_steps → approvals
     + notifications (استاندارد Laravel) → notification_preferences
M11: review_cycles → goals → kpis → reviews → feedbacks
     + courses → trainings → certificates
     + asset_categories → assets → asset_assignments
     + saved_reports → export_jobs
```

---

## ۲. نمای کلی روابط

```
companies ──┬── branches ──< departments (parent_id: درخت) ──< teams
            │                    │                              │
            │                    ├── positions                  │
            │                    └── employees ──< همهٔ فرزندان │
            └── holidays / shifts / leave_types / ... (scope شرکت)

users ──< user_roles >── roles ──< role_permissions >── permissions
  │ 1:1 (nullable)
  └── employees ──┬── employee_contacts (1:1) ── employee_bank_accounts (1:N)
                  ├── compensations (1:N, تاریخ‌دار)
                  ├── contracts ──< contract_types
                  ├── documents ──< document_types ──< document_versions
                  ├── attendances ──< shifts ──< shift_templates
                  ├── leave_requests ──< leave_types ── leave_balances
                  ├── applications? نه ← applicants ──< applications ──< job_openings
                  ├── asset_assignments ──< assets ──< asset_categories
                  ├── reviews/goals/trainings (اجرا/آموزش)
                  └── requests ──< request_types ──< approvals ──< workflow_steps

audit_logs ── (morph به همه، بدون FK)      notifications ── (morph استاندارد)
```

---

## ۳. Identity — M2

### ۳.۱ users (ALTER روی جدول اسکلت)

| ستون | نوع | توضیح |
|---|---|---|
| (اسکلت) id/name/email/email_verified_at/password/remember_token/timestamps | — | حفظ می‌شود |
| `is_active` | `BOOLEAN DEFAULT TRUE` | غیرفعال‌سازی بدون حذف |
| `last_login_at` | `DATETIME NULL` | آخرین ورود موفق (UTC) |
| `password_changed_at` | `DATETIME NULL` | برای سیاست انقضای رمز |
| `mfa_enforced` | `BOOLEAN DEFAULT FALSE` | الزام MFA برای این کاربر |

### ۳.۲ roles

| ستون | نوع |
|---|---|
| id | PK |
| `name` | `VARCHAR(100) UNIQUE` (مثلاً `HR Admin`) |
| `slug` | `VARCHAR(100) UNIQUE` (مثلاً `hr-admin`) |
| `description` | `VARCHAR(255) NULL` |
| timestamps | |

Seed پیش‌فرض (قابل ویرایش): super-admin · hr-admin · hr-manager ·
department-manager · team-lead · employee · finance · auditor.

### ۳.۳ permissions

| ستون | نوع |
|---|---|
| id | PK |
| `name` | `VARCHAR(150) UNIQUE` (مثلاً `employee.salary.view`) |
| `group` | `VARCHAR(100)` (مثلاً `employee`) — برای نمایش دسته‌بندی‌شده |
| `description` | `VARCHAR(255) NULL` |
| timestamps | |

فهرست کامل در `AUTHORIZATION.md` (M2).

### ۳.۴ role_user / permission_role (pivot)

| جدول | ستون‌ها |
|---|---|
| `role_user` | `role_id FK→roles CASCADE` · `user_id FK→users CASCADE` · PK(role_id, user_id) |
| `permission_role` | `permission_id FK→permissions CASCADE` · `role_id FK→roles CASCADE` · PK(permission_id, role_id) |

### ۳.۵ mfa_methods

| ستون | نوع |
|---|---|
| id | PK |
| `user_id` | FK→users CASCADE · INDEX |
| `type` | `VARCHAR(20)` (`totp`) |
| `secret` | `TEXT` رمزنگاری‌شده |
| `recovery_codes` | `TEXT NULL` رمزنگاری‌شده (JSON هش‌شده) |
| `is_primary` | `BOOLEAN DEFAULT FALSE` |
| `last_used_at` | `DATETIME NULL` |
| timestamps | |
| UNIQUE | `(user_id, type)` |

### ۳.۶ personal_access_tokens (استاندارد Sanctum)

`id` · `tokenable_type/VARCHAR` · `tokenable_id/BIGINT` · `name` ·
`token VARCHAR(64) UNIQUE` (هش) · `abilities TEXT NULL` · `last_used_at NULL` ·
`expires_at NULL` · timestamps · INDEX(tokenable).

### ۳.۷ audit_logs (فقط افزودنی)

| ستون | نوع |
|---|---|
| id | PK (BIGINT) |
| `user_id` | `BIGINT NULL` INDEX (بدون FK — کاربر حذف‌شده ردیابی را نمی‌شکند) |
| `event` | `VARCHAR(50)` INDEX (`created/updated/deleted/login/login_failed/...`) |
| `auditable_type` | `VARCHAR(150)` INDEX |
| `auditable_id` | `BIGINT NULL` INDEX |
| `ip_address` | `VARCHAR(45) NULL` |
| `user_agent` | `TEXT NULL` |
| `url` | `VARCHAR(500) NULL` |
| `old_values` / `new_values` | `JSON NULL` (بدون مقادیر حساس رمزنگاری‌نشده) |
| `created_at` | `DATETIME` INDEX |

> بدون `updated_at` · بدون `deleted_at` · بدون مسیر حذف در هیچ لایه‌ای.
> پیاده‌سازی M2: ایندکس morph به‌صورت composite روی `(auditable_type, auditable_id)`
> ساخته شد (به‌جای دو ایندکس جدا) چون همهٔ کوئری‌های morph هر دو ستون را با هم
> فیلتر می‌کنند؛ `event` و `user_id` و `created_at` ایندکس جدا دارند.

---

## ۴. Organization — M3

### ۴.۱ companies

| ستون | نوع |
|---|---|
| id | PK |
| `name` | `VARCHAR(150)` (مثلاً `قطعه‌رسان`) |
| `legal_name` | `VARCHAR(200) NULL` |
| `phone` / `email` | `VARCHAR(50/150) NULL` |
| `address` | `TEXT NULL` |
| `logo_path` | `VARCHAR(255) NULL` |
| `is_active` | `BOOLEAN DEFAULT TRUE` |
| timestamps + softDeletes | |

> ستون `company_id` روی همهٔ جداول سازمانی از روز اول؛ اما Tenant Isolation
> (Global Scope) تا نیاز واقعی **غیرفعال** می‌ماند (تصمیم D-4).

### ۴.۲ branches

| ستون | نوع |
|---|---|
| id | PK |
| `company_id` | FK→companies RESTRICT · INDEX |
| `name` / `code` | `VARCHAR(150/50)` · UNIQUE(company_id, code) |
| `phone` / `address` | NULL |
| `manager_id` | `BIGINT NULL` — **FK به employees در M4 اضافه می‌شود** |
| `is_active` | `BOOLEAN DEFAULT TRUE` |
| timestamps + softDeletes | |

### ۴.۳ departments (درخت)

| ستون | نوع |
|---|---|
| id | PK |
| `company_id` | FK→companies RESTRICT · INDEX |
| `branch_id` | FK→branches NULLOnDelete · INDEX NULL |
| `parent_id` | self-FK NULL · INDEX (`NULL` = ریشه) |
| `name` / `code` | `VARCHAR(150/50)` · UNIQUE(company_id, code) |
| `path` | `VARCHAR(500)` materialized path (`/1/4/9/`) · INDEX |
| `depth` | `INT DEFAULT 0` |
| `manager_id` | `BIGINT NULL` — **FK در M4** |
| `is_active` | `BOOLEAN DEFAULT TRUE` |
| timestamps + softDeletes | |
| INDEX | `(company_id, is_active)` |

### ۴.۴ positions

| ستون | نوع |
|---|---|
| id | PK |
| `company_id` | FK→companies RESTRICT · INDEX |
| `department_id` | FK→departments NULLOnDelete · INDEX NULL |
| `title` / `code` | `VARCHAR(150)` / `VARCHAR(50) NULL` · UNIQUE(company_id, code) |
| `level` | `INT DEFAULT 0` (رتبهٔ سازمانی) |
| `description` | `TEXT NULL` |
| `is_active` | `BOOLEAN DEFAULT TRUE` |
| timestamps + softDeletes | |

### ۴.۵ teams

| ستون | نوع |
|---|---|
| id | PK |
| `department_id` | FK→departments RESTRICT · INDEX |
| `name` | `VARCHAR(150)` |
| `lead_id` | `BIGINT NULL` — **FK به employees در M4** |
| `is_active` | `BOOLEAN DEFAULT TRUE` |
| timestamps + softDeletes | |

---

## ۵. People — M4

### ۵.۱ employees

| ستون | نوع |
|---|---|
| id | PK |
| `user_id` | FK→users NULLOnDelete · **UNIQUE NULL** (ارتباط ۱:۱ اختیاری) |
| `company_id` | FK→companies RESTRICT · INDEX |
| `branch_id` | FK→branches NULLOnDelete · INDEX NULL |
| `department_id` | FK→departments NULLOnDelete · INDEX NULL |
| `position_id` | FK→positions NULLOnDelete · INDEX NULL |
| `manager_id` | self-FK NULLOnDelete · INDEX NULL |
| `employee_no` | `VARCHAR(30)` · UNIQUE(company_id, employee_no) (مثلاً `EMP-0001`) |
| `first_name` / `last_name` | `VARCHAR(100)` |
| `national_id` | `TEXT NULL` **رمزنگاری‌شده** (کد ملی) |
| `birth_date` | `DATE NULL` |
| `gender` | `VARCHAR(20) DEFAULT 'unspecified'` |
| `marital_status` | `VARCHAR(20) NULL` |
| `profile_photo_path` | `VARCHAR(255) NULL` |
| `hire_date` | `DATE` |
| `employment_status` | `VARCHAR(30) DEFAULT 'active'` (`active/on_leave/suspended/terminated/resigned/retired`) |
| `employment_type` | `VARCHAR(30)` (`full_time/part_time/contractor/intern`) |
| `work_location` | `VARCHAR(150) NULL` |
| `lifecycle_state` | `VARCHAR(30) DEFAULT 'active'` (`hired/active/on_leave/offboarded/former`) |
| `lifecycle_changed_at` | `DATETIME NULL` |
| `terminated_at` | `DATE NULL` |
| `termination_reason` | `TEXT NULL` |
| `notes` | `TEXT NULL` (فقط HR — هرگز در خروجی عمومی) |
| timestamps + softDeletes | |
| INDEX | `(company_id, employment_status)` · `(company_id, department_id, employment_status)` |

### ۵.۲ employee_contacts (1:1)

| ستون | نوع |
|---|---|
| id | PK |
| `employee_id` | FK→employees CASCADE · **UNIQUE** |
| `mobile` / `email` / `phone` | `VARCHAR(30/150/30) NULL` |
| `address` | `TEXT NULL` |
| `city` / `postal_code` | `VARCHAR(100/20) NULL` |
| `emergency_name` / `emergency_phone` / `emergency_relation` | `VARCHAR NULL` |
| timestamps | |

### ۵.۳ employee_bank_accounts (1:N)

| ستون | نوع |
|---|---|
| id | PK |
| `employee_id` | FK→employees CASCADE · INDEX |
| `bank_name` | `VARCHAR(100) NULL` |
| `account_no` / `iban` | `TEXT` **رمزنگاری‌شده** |
| `card_no` | `TEXT NULL` **رمزنگاری‌شده** |
| `is_primary` | `BOOLEAN DEFAULT FALSE` |
| timestamps | |

### ۵.۴ compensations (تاریخ‌دار — پایهٔ Payroll در M12)

| ستون | نوع |
|---|---|
| id | PK |
| `employee_id` | FK→employees RESTRICT · INDEX |
| `effective_from` | `DATE` · INDEX |
| `effective_to` | `DATE NULL` |
| `base_salary` | `DECIMAL(15,2)` |
| `currency` | `CHAR(3) DEFAULT 'IRR'` |
| `allowances` / `deductions` | `JSON NULL` (ساختار در M12 نرمال می‌شود) |
| `notes` | `VARCHAR(255) NULL` |
| `created_by` | FK→users RESTRICT |
| timestamps | |
| INDEX | `(employee_id, effective_from)` |

---

## ۶. Time — M6/M7

### ۶.۱ work_schedules

| ستون | نوع |
|---|---|
| id | PK |
| `company_id` | FK→companies RESTRICT |
| `name` | `VARCHAR(150)` |
| `hours_per_day` | `DECIMAL(4,2) DEFAULT 8.00` |
| `workdays` | `JSON` (آرایهٔ روزهای هفته، شنبه=6 … جمعه=5؟ — قرارداد: 0=شنبه تا 6=جمعه، مستند در M6) |
| timestamps | |

### ۶.۲ shift_templates

| ستون | نوع |
|---|---|
| id | PK |
| `company_id` | FK→companies RESTRICT |
| `name` | `VARCHAR(150)` |
| `start_time` / `end_time` | `TIME` |
| `break_minutes` | `INT DEFAULT 0` |
| `is_flexible` | `BOOLEAN DEFAULT FALSE` |
| `grace_late_minutes` / `grace_early_minutes` | `INT DEFAULT 0` |
| timestamps + softDeletes | |

> شیفت شب (overnight): `end_time < start_time` — منطق در Service، نه ستون جدا.

### ۶.۳ shifts (نمونهٔ تاریخ‌دار شیفت)

| ستون | نوع |
|---|---|
| id | PK |
| `company_id` | FK→companies RESTRICT · INDEX |
| `template_id` | FK→shift_templates NULLOnDelete NULL |
| `employee_id` | FK→employees CASCADE NULL (NULL = شیفت دپارتمان/تیم) |
| `department_id` / `team_id` | FK NULL (scope شیفت گروهی) |
| `date` | `DATE` · INDEX |
| `start_time` / `end_time` | `TIME` |
| `break_minutes` | `INT DEFAULT 0` |
| `notes` | `VARCHAR(255) NULL` |
| timestamps | |
| UNIQUE | `(employee_id, date)` (NULLها در MySQL تکراری محسوب نمی‌شوند) |

### ۶.۴ attendances

| ستون | نوع |
|---|---|
| id | PK |
| `employee_id` | FK→employees RESTRICT · INDEX |
| `company_id` | FK→companies RESTRICT · INDEX |
| `date` | `DATE` · INDEX |
| `shift_id` | FK→shifts NULLOnDelete NULL |
| `check_in` / `check_out` | `DATETIME NULL` (**UTC**) |
| `break_minutes` | `INT DEFAULT 0` |
| `work_minutes` | `INT NULL` (محاسبه‌شده) |
| `overtime_minutes` / `late_minutes` / `early_minutes` | `INT DEFAULT 0` |
| `status` | `VARCHAR(20)` (`present/absent/late/early_leave/on_leave/holiday/mission`) |
| `source` | `VARCHAR(20)` (`manual/device/self/import`) |
| `note` | `VARCHAR(255) NULL` |
| timestamps | |
| UNIQUE | `(employee_id, date)` |
| INDEX | `(company_id, date)` · `(date, status)` |

### ۶.۵ leave_types

| ستون | نوع |
|---|---|
| id | PK |
| `company_id` | FK→companies RESTRICT |
| `name` / `code` | `VARCHAR(100/50)` · UNIQUE(company_id, code) |
| `paid` | `BOOLEAN DEFAULT TRUE` |
| `annual_quota` | `INT NULL` (روز در سال) |
| `requires_attachment` | `BOOLEAN DEFAULT FALSE` |
| `max_consecutive_days` | `INT NULL` |
| `is_active` | `BOOLEAN DEFAULT TRUE` |
| timestamps | |

Seed: استحقاقی · استعلاجی · بدون حقوق · اضطراری.

### ۶.۶ leave_balances

| ستون | نوع |
|---|---|
| id | PK |
| `employee_id` | FK→employees CASCADE |
| `leave_type_id` | FK→leave_types RESTRICT |
| `year` | `INT` (سال شمسی، مثلاً 1405) |
| `total_days` / `used_days` / `carried_days` | `DECIMAL(5,1)` |
| timestamps | |
| UNIQUE | `(employee_id, leave_type_id, year)` |

### ۶.۷ leave_requests

| ستون | نوع |
|---|---|
| id | PK |
| `employee_id` | FK→employees RESTRICT · INDEX |
| `leave_type_id` | FK→leave_types RESTRICT |
| `from_date` / `to_date` | `DATE` |
| `days` | `DECIMAL(5,1)` |
| `reason` | `TEXT NULL` |
| `attachment_path` | `VARCHAR(255) NULL` (دیسک خصوصی؛ بدون FK — ساده و کافی) |
| `status` | `VARCHAR(20) DEFAULT 'pending'` (`pending/approved/rejected/cancelled`) |
| `decided_by` | FK→users NULLOnDelete NULL |
| `decided_at` | `DATETIME NULL` |
| `decision_note` | `VARCHAR(500) NULL` |
| timestamps + softDeletes | |
| INDEX | `(employee_id, status)` · `(status, from_date)` |

### ۶.۸ holidays

| ستون | نوع |
|---|---|
| id | PK |
| `company_id` | FK→companies CASCADE NULL (NULL = رسمی کشوری) |
| `branch_id` / `department_id` | FK NULL (تعطیلی محدود) |
| `date` | `DATE` · INDEX |
| `title` | `VARCHAR(150)` |
| `kind` | `VARCHAR(20)` (`official/company/department`) |
| `recurring_rule` | `VARCHAR(50) NULL` (مثلاً `jalali:01-01`) |
| timestamps | |

---

## ۷. Documents & Contracts — M8

### ۷.۱ document_types

| ستون | نوع |
|---|---|
| id | PK |
| `company_id` | FK→companies RESTRICT |
| `name` / `code` | `VARCHAR(100/50)` · UNIQUE(company_id, code) |
| `requires_expiry` | `BOOLEAN DEFAULT FALSE` |
| `max_size_kb` | `INT DEFAULT 10240` |
| `allowed_mimes` | `JSON NULL` |
| timestamps | |

### ۷.۲ documents

| ستون | نوع |
|---|---|
| id | PK |
| `company_id` | FK→companies RESTRICT |
| `employee_id` | FK→employees RESTRICT NULL (NULL = سند سطح شرکت) |
| `type_id` | FK→document_types RESTRICT |
| `title` | `VARCHAR(200)` |
| `disk` | `VARCHAR(30) DEFAULT 'hrm_private'` |
| `path` | `VARCHAR(255)` (نام تصادفی، خارج از webroot) |
| `original_name` / `mime` | `VARCHAR(255/100)` |
| `size_bytes` | `BIGINT` |
| `checksum` | `CHAR(64)` (sha256) |
| `version` | `INT DEFAULT 1` |
| `expires_at` | `DATE NULL` · INDEX |
| `visibility` | `VARCHAR(20)` (`hr_only/manager/self`) |
| `uploaded_by` | FK→users RESTRICT |
| timestamps + softDeletes | |
| INDEX | `(employee_id, type_id)` |

### ۷.۳ document_versions (فقط افزودنی)

`id` · `document_id FK→documents RESTRICT` · `version INT` · `path` ·
`size_bytes` · `checksum` · `uploaded_by FK→users RESTRICT` · `created_at` ·
UNIQUE(document_id, version).

### ۷.۴ contract_types

`id` · `company_id FK→companies RESTRICT` · `name/code` (UNIQUE per company) ·
`description NULL` · timestamps.

### ۷.۵ contracts

| ستون | نوع |
|---|---|
| id | PK |
| `employee_id` | FK→employees RESTRICT · INDEX |
| `company_id` | FK→companies RESTRICT |
| `contract_type_id` | FK→contract_types RESTRICT |
| `contract_no` | `VARCHAR(50)` · UNIQUE(company_id, contract_no) |
| `start_date` | `DATE` |
| `end_date` | `DATE NULL` · INDEX (برای هشدار انقضا) |
| `signed_at` | `DATETIME NULL` |
| `status` | `VARCHAR(20)` (`draft/active/expired/terminated/renewed`) |
| `compensation_id` | FK→compensations NULLOnDelete NULL |
| `document_id` | FK→documents NULLOnDelete NULL (فایل قرارداد) |
| `terms` | `TEXT NULL` |
| timestamps + softDeletes | |
| INDEX | `(employee_id, status)` |

---

## ۸. Talent / ATS — M9

### ۸.۱ job_openings

| ستون | نوع |
|---|---|
| id | PK |
| `company_id` | FK→companies RESTRICT |
| `department_id` / `position_id` | FK NULL |
| `title` / `code` | `VARCHAR(200)` / `VARCHAR(50)` · UNIQUE(company_id, code) |
| `description` / `requirements` | `TEXT` |
| `employment_type` | `VARCHAR(30)` |
| `location` | `VARCHAR(150) NULL` |
| `salary_min` / `salary_max` | `DECIMAL(15,2) NULL` + `currency CHAR(3) DEFAULT 'IRR'` |
| `status` | `VARCHAR(20)` (`draft/published/closed/archived`) |
| `published_at` / `closed_at` | `DATETIME NULL` |
| `created_by` | FK→users RESTRICT |
| timestamps + softDeletes | |

### ۸.۲ pipeline_stages (مراحل قابل تنظیم هر آگهی)

`id` · `job_opening_id FK→job_openings CASCADE` · `name/slug` ·
`position INT` · `kind (inbox/screen/interview/evaluation/offer/hired/rejected)` ·
timestamps · UNIQUE(job_opening_id, slug). Seed پیش‌فرض ۷ مرحله‌ای.

### ۸.۳ applicants

| ستون | نوع |
|---|---|
| id | PK |
| `first_name` / `last_name` | `VARCHAR(100)` |
| `national_id` | `TEXT NULL` رمزنگاری‌شده |
| `mobile` / `email` | `VARCHAR(30/150)` · INDEX هرکدام |
| `birth_date` / `city` | `DATE NULL` / `VARCHAR(100) NULL` |
| `resume_document_id` | FK→documents NULLOnDelete NULL |
| `cover_letter` | `TEXT NULL` |
| `source` / `source_detail` | `VARCHAR(30)` (`website/referral/other`) / `VARCHAR NULL` |
| timestamps + softDeletes | |

### ۸.۴ applications

| ستون | نوع |
|---|---|
| id | PK |
| `job_opening_id` | FK→job_openings RESTRICT |
| `applicant_id` | FK→applicants RESTRICT |
| `current_stage_id` | FK→pipeline_stages NULLOnDelete NULL |
| `rating` | `TINYINT NULL` (۱–۵) |
| `notes` | `TEXT NULL` |
| `applied_at` | `DATETIME` |
| timestamps | |
| UNIQUE | `(job_opening_id, applicant_id)` |

### ۸.۵ interviews / interview_interviewers / evaluations / offers

| جدول | ستون‌های کلیدی |
|---|---|
| `interviews` | `application_id FK CASCADE` · `scheduled_at DATETIME UTC` · `duration_minutes` · `mode (in_person/online/phone)` · `location VARCHAR NULL` · `status (scheduled/done/cancelled/no_show)` · `notes` · timestamps |
| `interview_interviewers` | `interview_id FK CASCADE` · `user_id FK CASCADE` · PK(interview_id, user_id) |
| `evaluations` | `application_id FK CASCADE` · `interview_id FK NULL` · `evaluator_id FK→users RESTRICT` · `score INT NULL (0–100)` · `strengths/weaknesses TEXT NULL` · `recommendation (hire/no_hire/maybe)` · timestamps |
| `offers` | `application_id FK→applications RESTRICT UNIQUE` · `salary DECIMAL + currency` · `start_date NULL` · `expires_at NULL` · `status (draft/sent/accepted/rejected/expired)` · `letter_document_id FK→documents NULL` · timestamps |

---

## ۹. Requests & Workflow — M10

| جدول | ستون‌های کلیدی |
|---|---|
| `request_types` | `company_id FK` · `name/code` (UNIQUE per company) · `schema JSON` (فیلدهای فرم پویا) · `requires_attachment BOOL` · `is_active` · timestamps |
| `requests` | `employee_id FK RESTRICT` · `type_id FK RESTRICT` · `title` · `payload JSON` · `status (draft/pending/approved/rejected/cancelled)` · `current_step INT DEFAULT 0` · timestamps + softDeletes · INDEX(employee_id, status),(status) |
| `workflow_definitions` | `company_id FK` · `request_type_id FK UNIQUE` · `name` · timestamps |
| `workflow_steps` | `workflow_id FK CASCADE` · `position INT` · `name` · `approver_type (manager/department_manager/role/user)` · `approver_role_id FK→roles NULL` · `approver_user_id FK→users NULL` · `sla_hours NULL` · timestamps · UNIQUE(workflow_id, position) |
| `approvals` | `request_id FK CASCADE` · `step_id FK→workflow_steps RESTRICT` · `approver_id FK→users RESTRICT` · `decision (approved/rejected)` · `note NULL` · `decided_at DATETIME` · timestamps · UNIQUE(request_id, step_id) |

> مرخصی گردش سادهٔ اختصاصی خود را دارد (مدیر → HR)؛ این موتور برای
> سایر درخواست‌ها (تجهیزات، گواهی، درخواست HR) است.

### اعلان‌ها (M5/M10)

- `notifications`: جدول استاندارد Laravel
  (`id UUID` · `type` · `notifiable_type/id` · `data JSON` · `read_at NULL` · timestamps).
- `notification_preferences`: `user_id FK CASCADE` · `event VARCHAR(100)` ·
  `channel_mail/inapp BOOL DEFAULT TRUE` · `channel_sms BOOL DEFAULT FALSE` ·
  timestamps · UNIQUE(user_id, event).

---

## ۱۰. Performance · Training · Assets — M11

### عملکرد

| جدول | ستون‌های کلیدی |
|---|---|
| `review_cycles` | `company_id FK` · `name` · `starts_at/ends_at DATE` · `status (draft/active/closed)` · timestamps |
| `kpis` | `company_id FK` · `department_id FK NULL` · `title` · `unit NULL` · `target DECIMAL(12,2) NULL` · timestamps |
| `goals` | `employee_id FK CASCADE` · `cycle_id FK NULL` · `kpi_id FK NULL` · `title` · `description NULL` · `weight INT DEFAULT 0` · `status (draft/active/done/cancelled)` · `progress INT DEFAULT 0` · `due_date NULL` · timestamps |
| `reviews` | `cycle_id FK RESTRICT` · `employee_id FK RESTRICT` · `reviewer_id FK→users RESTRICT` · `kind (self/manager/peer)` · `status (draft/submitted)` · `overall_score DECIMAL(5,2) NULL` · `strengths/weaknesses/improvements TEXT NULL` · `submitted_at NULL` · timestamps · UNIQUE(cycle_id, employee_id, reviewer_id, kind) |
| `feedbacks` | `employee_id FK CASCADE` · `author_id FK→users CASCADE` · `body TEXT` · `is_anonymous BOOL DEFAULT FALSE` · timestamps |

### آموزش

| جدول | ستون‌های کلیدی |
|---|---|
| `courses` | `company_id FK` · `title` · `code NULL` · `provider NULL` · `duration_hours NULL` · `description NULL` · `is_active` · timestamps |
| `trainings` | `course_id FK RESTRICT` · `employee_id FK CASCADE` · `status (enrolled/attended/completed/failed/cancelled)` · `score NULL` · `started_at/completed_at DATE NULL` · timestamps · INDEX(course_id, employee_id) |
| `certificates` | `training_id FK→trainings RESTRICT UNIQUE` · `employee_id FK CASCADE` · `title` · `issuer NULL` · `issued_at DATE` · `expires_at DATE NULL` · `document_id FK→documents NULL` · timestamps |

### دارایی‌ها

| جدول | ستون‌های کلیدی |
|---|---|
| `asset_categories` | `company_id FK` · `name/code` (UNIQUE per company) · timestamps |
| `assets` | `company_id FK` · `category_id FK RESTRICT` · `name` · `asset_tag VARCHAR(50) UNIQUE` · `serial NULL` · `purchase_date NULL` · `purchase_price DECIMAL(15,2) NULL + currency` · `status (in_stock/assigned/in_repair/retired/lost)` · `notes NULL` · timestamps + softDeletes |
| `asset_assignments` | `asset_id FK RESTRICT` · `employee_id FK RESTRICT` · `assigned_at DATETIME` · `returned_at DATETIME NULL` · `condition_out/in NULL` · `notes NULL` · timestamps · INDEX(asset_id, returned_at),(employee_id) |

### گزارش‌ها

| جدول | ستون‌های کلیدی |
|---|---|
| `saved_reports` | `user_id FK CASCADE` · `name` · `kind VARCHAR(50)` · `definition JSON` · `is_shared BOOL DEFAULT FALSE` · timestamps |
| `export_jobs` | `user_id FK CASCADE` · `kind` · `status (queued/processing/done/failed)` · `params JSON` · `file_path NULL` (دیسک خصوصی) · `error NULL` · timestamps |

---

## ۱۱. Seed (فقط دادهٔ Fake)

| محیط | Seed مجاز |
|---|---|
| development | همه‌چیز Fake: شرکت نمونه · دپارتمان‌ها · سمت‌ها · نقش‌ها/مجوزها · ۲۰ کارمند · انواع مرخصی · شیفت‌ها · تعطیلات · آگهی نمونه |
| staging | همان development (هرگز کپی production) |
| production | فقط دادهٔ پایهٔ غیرشخصی: نقش‌ها/مجوزها · انواع مرخصی · انواع سند · مراحل پایپ‌لاین · تعطیلات رسمی |

> **ممنوعیت مطلق:** نام واقعی، کد ملی واقعی، حقوق واقعی، شماره حساب واقعی،
> آدرس واقعی — در هیچ Seed، فیکسچر یا تست. Faker با locale ‏`fa_IR` برای دادهٔ فارسی.

## ۱۲. نگهداری

- `migrations` جدول استاندارد Laravel (نسخه‌بندی واقعی — برخلاف سامانهٔ مدیریت).
- هر migration: `up()` + `down()` کامل؛ FKها نام صریح ندارند (پیش‌فرض).
- تغییر ستون حساس (مثلاً حقوق) → migration + ثبت در Audit + به‌روزرسانی این سند.
- بررسی ماهانه: ایندکس‌های بلااستفاده، رشد `audit_logs` (آرشیو پس از ۲ سال، هرگز حذف).
