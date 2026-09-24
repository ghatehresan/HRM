<x-layouts.app title="ویرایش کاربر">
    <x-page-head title="ویرایش کاربر" :sub="$user->name">
        <x-slot:actions>
            <x-btn href="{{ route('admin.users.index') }}">بازگشت به فهرست</x-btn>
        </x-slot:actions>
    </x-page-head>

    <x-card title="مشخصات">
        <form method="POST" action="{{ route('admin.users.update', $user) }}" class="max-w-[560px]">
            @csrf
            @method('PUT')
            @include('admin.users._fields', ['user' => $user, 'roles' => $roles, 'selectedRoles' => $selectedRoles])
            <div class="mt-2">
                <x-btn type="submit" tone="primary">ذخیره تغییرات</x-btn>
            </div>
        </form>
    </x-card>
</x-layouts.app>
