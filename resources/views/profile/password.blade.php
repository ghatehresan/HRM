<x-layouts.app title="تغییر رمز عبور">
    <x-page-head title="تغییر رمز عبور" sub="پس از تغییر، همهٔ توکن‌های API شما باطل می‌شود" />

    <x-card title="رمز جدید">
        <form method="POST" action="{{ route('password.change.update') }}" class="max-w-[440px]">
            @csrf
            @method('PUT')
            <x-form.input name="current_password" label="رمز عبور فعلی" type="password" required autocomplete="current-password" />
            <x-form.input name="password" label="رمز جدید" type="password" required autocomplete="new-password" hint="حداقل ۱۲ کاراکتر؛ ترکیب غیرقابل‌حدس انتخاب کنید." />
            <x-form.input name="password_confirmation" label="تکرار رمز جدید" type="password" required autocomplete="new-password" />
            <x-btn type="submit" tone="primary">تغییر رمز</x-btn>
        </form>
    </x-card>
</x-layouts.app>
