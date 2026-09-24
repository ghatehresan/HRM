<x-layouts.app title="نقش جدید">
    <x-page-head title="نقش جدید" sub="تعریف نقش و مجوزهایش" />

    <x-card title="مشخصات">
        <form method="POST" action="{{ route('admin.roles.store') }}" class="max-w-[640px]">
            @csrf
            @include('admin.roles._fields', ['role' => new \App\Models\Role(), 'grouped' => $grouped, 'selectedPerms' => $selected])
            <div class="mt-2 flex gap-2">
                <x-btn type="submit" tone="primary">ساخت نقش</x-btn>
                <x-btn href="{{ route('admin.roles.index') }}">انصراف</x-btn>
            </div>
        </form>
    </x-card>
</x-layouts.app>
