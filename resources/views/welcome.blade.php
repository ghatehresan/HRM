<x-layouts.app title="خانه">
    <x-page-head title="منابع انسانی قطعه‌رسان" sub="بنیان سیستم — Milestone 1">
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

    <x-card title="وضعیت Milestone 1">
        <div class="flex flex-wrap items-center gap-2">
            <x-badge tone="green">معماری تأیید شد</x-badge>
            <x-badge tone="info">Design System پایه</x-badge>
            <x-badge tone="navy">تقویم شمسی</x-badge>
            <x-badge tone="amber">احراز هویت: در Milestone 2</x-badge>
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
        <x-empty-state title="هنوز ماژولی فعال نشده است" sub="ماژول احراز هویت و مجوزها در Milestone 2 اضافه می‌شود." icon="▦" />
    </x-card>
</x-layouts.app>
