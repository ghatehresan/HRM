<x-layouts.app title="خانه">
    <x-page-head title="منابع انسانی قطعه‌رسان" sub="بنیان سیستم — Milestone 2">
        <x-slot:actions>
            <x-btn href="{{ route('health') }}" tone="navy">سلامت سیستم</x-btn>
        </x-slot:actions>
    </x-page-head>

    <div class="mb-5 grid grid-cols-2 gap-4 tablet:grid-cols-4">
        <x-stat label="محیط اجرا" :value="app()->environment()" />
        <x-stat label="نسخهٔ PHP" :value="PHP_VERSION" />
        <x-stat label="نسخهٔ لاراول" :value="app()->version()" />
        <x-stat label="امروز" :value="\App\Support\Jalali::format(date('Y-m-d'), 'long')" />
    </div>

    @guest
        <x-card title="شروع">
            <p class="mb-3 text-[13.5px] leading-7 text-steel">
                برای ادامه وارد شوید. اگر حسابی ندارید، مدیر سیستم باید برایتان بسازد؛
                ثبت‌نام عمومی نداریم.
            </p>
            <x-btn href="{{ route('login') }}" tone="primary">ورود به سیستم</x-btn>
        </x-card>
    @endguest

    <x-card title="وضعیت Milestone 2">
        <div class="flex flex-wrap items-center gap-2">
            <x-badge tone="green">معماری تأیید شد</x-badge>
            <x-badge tone="info">Design System پایه</x-badge>
            <x-badge tone="navy">تقویم شمسی</x-badge>
            <x-badge tone="green">هویت و دسترسی</x-badge>
        </div>
        <p class="mt-3 text-[13.5px] leading-7 text-steel">
            این صفحه با لی‌اوت RTL، فونت وزیرمتن خودمیزبانی‌شده و توکن‌های کتاب برند
            (سرمه‌ای <span class="font-en" dir="ltr">#0E2A47</span> و نارنجی
            <span class="font-en" dir="ltr">#F26B1D</span>) رندر شده است.
        </p>
        <div class="mt-3 flex flex-wrap gap-2">
            <x-btn tone="primary">دکمهٔ اصلی</x-btn>
            <x-btn tone="navy">دکمهٔ سرمه‌ای</x-btn>
            <x-btn>دکمهٔ معمولی</x-btn>
            <x-btn tone="danger">دکمهٔ خطر</x-btn>
        </div>
    </x-card>

    <x-card title="ماژول‌ها">
        @auth
            <div class="flex flex-wrap gap-2">
                @can('users.view')
                    <x-btn href="{{ route('admin.users.index') }}" tone="navy">کاربران</x-btn>
                @endcan
                @can('roles.view')
                    <x-btn href="{{ route('admin.roles.index') }}" tone="navy">نقش‌ها</x-btn>
                @endcan
                @can('audit.view')
                    <x-btn href="{{ route('admin.audit-logs.index') }}" tone="navy">لاگ حسابرسی</x-btn>
                @endcan
            </div>
        @else
            <x-empty-state title="پس از ورود، ماژول‌های شما اینجا نمایش داده می‌شود" sub="دسترسی هر کاربر به نقش او بستگی دارد." icon="▦" />
        @endauth
    </x-card>
</x-layouts.app>
