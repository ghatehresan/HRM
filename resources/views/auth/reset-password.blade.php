<x-layouts.guest title="تعیین رمز جدید">
    <form method="POST" action="{{ route('password.update') }}">
        @csrf
        <input type="hidden" name="token" value="{{ $token }}">
        <x-form.input name="email" label="ایمیل" type="email" required ltr :value="request('email', '')" autocomplete="username" />
        <x-form.input name="password" label="رمز جدید" type="password" required autocomplete="new-password" hint="حداقل ۱۲ کاراکتر؛ ترکیب غیرقابل‌حدس انتخاب کنید." />
        <x-form.input name="password_confirmation" label="تکرار رمز جدید" type="password" required autocomplete="new-password" />
        <x-btn type="submit" tone="primary" class="w-full">تغییر رمز و ورود</x-btn>
    </form>
</x-layouts.guest>
