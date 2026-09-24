<x-layouts.guest title="بازیابی رمز عبور">
    <p class="mb-4 text-[13.5px] leading-7 text-steel">
        ایمیلی که با آن ثبت‌نام شده‌اید را وارد کنید؛ اگر حسابی با این ایمیل
        وجود داشته باشد، پیوند بازیابی برایتان ارسال می‌شود.
    </p>
    <form method="POST" action="{{ route('password.email') }}">
        @csrf
        <x-form.input name="email" label="ایمیل" type="email" required ltr autocomplete="username" />
        <x-btn type="submit" tone="primary" class="w-full">ارسال پیوند بازیابی</x-btn>
    </form>
    <div class="mt-4 text-center text-[13px]">
        <a href="{{ route('login') }}" class="font-medium text-navy hover:underline">بازگشت به ورود</a>
    </div>
</x-layouts.guest>
