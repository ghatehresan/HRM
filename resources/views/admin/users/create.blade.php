<x-layouts.app title="کاربر جدید">
    <x-page-head title="کاربر جدید" sub="ساخت حساب ورود" />

    <x-card title="مشخصات">
        <form method="POST" action="{{ route('admin.users.store') }}" class="max-w-[560px]">
            @csrf
            @include('admin.users._fields', ['user' => new \App\Models\User(), 'roles' => $roles, 'selectedRoles' => []])
            <div class="mt-2 flex gap-2">
                <x-btn type="submit" tone="primary">ساخت کاربر</x-btn>
                <x-btn href="{{ route('admin.users.index') }}">انصراف</x-btn>
            </div>
        </form>
    </x-card>
</x-layouts.app>
