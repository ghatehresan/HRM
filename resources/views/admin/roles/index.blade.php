<x-layouts.app title="نقش‌ها">
    <x-page-head title="نقش‌ها" sub="نقش‌ها مجموعه‌ای از مجوزها هستند">
        <x-slot:actions>
            @can('roles.manage')
                <x-btn href="{{ route('admin.roles.create') }}" tone="primary">نقش جدید</x-btn>
            @endcan
        </x-slot:actions>
    </x-page-head>

    <x-card>
        @if($roles->count())
            <div class="table-wrap">
                <table class="grid">
                    <thead><tr><th>نام</th><th>شناسه</th><th>توضیح</th><th>کاربران</th><th>مجوزها</th><th></th></tr></thead>
                    <tbody>
                    @foreach($roles as $role)
                        <tr>
                            <td class="font-bold text-navy">{{ $role->name }}</td>
                            <td class="font-en" dir="ltr">{{ $role->slug }}</td>
                            <td class="text-steel">{{ $role->description ?? '—' }}</td>
                            <td>{{ \App\Support\PersianNumbers::toFa((string) $role->users_count) }}</td>
                            <td>{{ \App\Support\PersianNumbers::toFa((string) $role->permissions_count) }}</td>
                            <td class="whitespace-nowrap text-left">
                                @can('roles.manage')
                                    <x-btn href="{{ route('admin.roles.edit', $role) }}" sm>ویرایش</x-btn>
                                @endcan
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
            <div class="mt-4">{{ $roles->links() }}</div>
        @else
            <x-empty-state title="نقشی ثبت نشده است" sub="اولین نقش را بسازید." />
        @endif
    </x-card>
</x-layouts.app>
