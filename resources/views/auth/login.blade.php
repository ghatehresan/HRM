<x-layouts.guest title="ورود">
    <form method="POST" action="{{ route('login.store') }}">
        @csrf
        <x-form.input name="email" label="ایمیل" type="email" required ltr autocomplete="username" />
        <x-form.input name="password" label="رمز عبور" type="password" required autocomplete="current-password" />
        <x-btn type="submit" tone="primary" class="w-full">ورود</x-btn>
    </form>
    <div class="mt-4 text-center text-[13px]">
        <a href="{{ route('password.request') }}" class="font-medium text-navy hover:underline">رمز را فراموش کرده‌ام</a>
    </div>
</x-layouts.guest>
