<x-layouts.app title="کاربران">
    <x-page-head title="کاربران" sub="حساب‌های ورود به سیستم">
        <x-slot:actions>
            @can('users.manage')
                <x-btn href="{{ route('admin.users.create') }}" tone="primary">کاربر جدید</x-btn>
            @endcan
        </x-slot:actions>
    </x-page-head>

    <x-card>
        <form method="GET" action="{{ route('admin.users.index') }}" class="mb-4 flex gap-2">
            <input type="search" name="q" value="{{ $q }}" placeholder="جست‌وجوی نام یا ایمیل…" class="field-input max-w-[320px]">
            <x-btn type="submit">جست‌وجو</x-btn>
            @if($q)
                <x-btn href="{{ route('admin.users.index') }}">پاک‌سازی</x-btn>
            @endif
        </form>

        @if($users->count())
            <div class="table-wrap">
                <table class="grid">
                    <thead><tr><th>نام</th><th>ایمیل</th><th>نقش‌ها</th><th>وضعیت</th><th>آخرین ورود</th><th></th></tr></thead>
                    <tbody>
                    @foreach($users as $u)
                        <tr>
                            <td class="font-bold text-navy">{{ $u->name }}</td>
                            <td class="font-en" dir="ltr">{{ $u->email }}</td>
                            <td>
                                <span class="flex flex-wrap gap-1">
                                    @forelse($u->roles as $role)
                                        <x-badge tone="navy">{{ $role->name }}</x-badge>
                                    @empty
                                        <span class="text-steel">—</span>
                                    @endforelse
                                </span>
                            </td>
                            <td>
                                @if($u->is_active)
                                    <x-badge tone="green">فعال</x-badge>
                                @else
                                    <x-badge tone="red">غیرفعال</x-badge>
                                @endif
                            </td>
                            <td class="whitespace-nowrap text-steel">{{ $u->last_login_at ? \App\Support\Jalali::formatInTimezone($u->last_login_at, config('hrm.display_timezone'), 'full') : '—' }}</td>
                            <td class="whitespace-nowrap text-left">
                                @can('users.manage')
                                    <x-btn href="{{ route('admin.users.edit', $u) }}" sm>ویرایش</x-btn>
                                @endcan
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
            <div class="mt-4">{{ $users->links() }}</div>
        @else
            <x-empty-state title="کاربری پیدا نشد" sub="عبارت جست‌وجو را تغییر دهید." />
        @endif
    </x-card>
</x-layouts.app>
