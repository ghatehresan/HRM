<x-layouts.app title="ویرایش نقش">
    <x-page-head title="ویرایش نقش" :sub="$role->name">
        <x-slot:actions>
            <x-btn href="{{ route('admin.roles.index') }}">بازگشت به فهرست</x-btn>
        </x-slot:actions>
    </x-page-head>

    <x-card title="مشخصات">
        <form method="POST" action="{{ route('admin.roles.update', $role) }}" class="max-w-[640px]">
            @csrf
            @method('PUT')
            @include('admin.roles._fields', ['role' => $role, 'grouped' => $grouped, 'selectedPerms' => $selected])
            <div class="mt-2">
                <x-btn type="submit" tone="primary">ذخیره تغییرات</x-btn>
            </div>
        </form>
    </x-card>
</x-layouts.app>
