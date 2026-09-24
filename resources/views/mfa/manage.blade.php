<x-layouts.app title="تأیید دومرحله‌ای">
    <x-page-head title="تأیید دومرحله‌ای" sub="مدیریت اپ احراز هویت و کدهای بازیابی" />

    <x-card title="وضعیت">
        <div class="flex flex-wrap items-center gap-2">
            <x-badge tone="green">فعال</x-badge>
            <span class="text-[13.5px] text-steel">{{ \App\Support\PersianNumbers::toFa((string) $codesLeft) }} کد بازیابی استفاده‌نشده باقی مانده است.</span>
        </div>
    </x-card>

    <x-card title="کدهای بازیابی جدید">
        <p class="mb-3 text-[13.5px] leading-7 text-steel">
            با ساخت کدهای جدید، کدهای قبلی باطل می‌شوند. کدهای جدید فقط
            یک‌بار نمایش داده می‌شوند؛ آن‌ها را در جای امنی نگه دارید.
        </p>
        <form method="POST" action="{{ route('mfa.codes.regenerate') }}">
            @csrf
            <x-btn type="submit" tone="navy">ساخت کدهای جدید</x-btn>
        </form>
    </x-card>

    <x-card title="غیرفعال‌سازی">
        <p class="mb-3 text-[13.5px] leading-7 text-steel">
            برای غیرفعال‌سازی، رمز عبور فعلی لازم است. اگر نقش شما نیازمند
            تأیید دومرحله‌ای باشد، در ورود بعدی دوباره باید فعالش کنید.
        </p>
        <form method="POST" action="{{ route('mfa.destroy') }}">
            @csrf
            @method('DELETE')
            <x-form.input name="password" label="رمز عبور فعلی" type="password" required autocomplete="current-password" />
            <x-btn type="submit" tone="danger">غیرفعال‌سازی تأیید دومرحله‌ای</x-btn>
        </form>
    </x-card>
</x-layouts.app>
