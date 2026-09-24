<x-layouts.guest title="تأیید دومرحله‌ای">
    <p class="mb-4 text-[13.5px] leading-7 text-steel">
        کد ۶ رقمی اپ احراز هویت را وارد کنید. اگر به اپ دسترسی ندارید،
        یکی از کدهای بازیابی را وارد کنید.
    </p>
    <form method="POST" action="{{ route('mfa.challenge.store') }}">
        @csrf
        <x-form.input name="code" label="کد تأیید یا بازیابی" required ltr autocomplete="one-time-code" />
        <x-btn type="submit" tone="primary" class="w-full">تأیید ورود</x-btn>
    </form>
    <form method="POST" action="{{ route('logout') }}" class="mt-3 text-center">
        @csrf
        <button type="submit" class="text-[13px] font-medium text-steel hover:underline">انصراف و خروج</button>
    </form>
</x-layouts.guest>
