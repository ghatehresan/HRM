<x-layouts.app title="لاگ حسابرسی">
    <x-page-head title="لاگ حسابرسی" sub="فقط خواندنی — هیچ مسیری برای ویرایش یا حذف وجود ندارد" />

    <x-card>
        <form method="GET" action="{{ route('admin.audit-logs.index') }}" class="mb-4 grid grid-cols-2 gap-3 tablet:grid-cols-5">
            <select name="event" class="field-input" aria-label="رویداد">
                <option value="">همهٔ رویدادها</option>
                @foreach($events as $e)
                    <option value="{{ $e }}" @selected($filters['event'] === $e)>{{ $e }}</option>
                @endforeach
            </select>
            <input type="search" name="email" value="{{ $filters['email'] }}" placeholder="ایمیل کاربر…" class="field-input" aria-label="ایمیل کاربر">
            <input type="date" name="from" value="{{ $filters['from'] }}" class="field-input ltr-input" aria-label="از تاریخ">
            <input type="date" name="to" value="{{ $filters['to'] }}" class="field-input ltr-input" aria-label="تا تاریخ">
            <x-btn type="submit">اعمال</x-btn>
        </form>

        @if($logs->count())
            <div class="table-wrap">
                <table class="grid">
                    <thead><tr><th>#</th><th>رویداد</th><th>کاربر</th><th>هدف</th><th>IP</th><th>زمان</th><th></th></tr></thead>
                    <tbody>
                    @foreach($logs as $log)
                        <tr>
                            <td class="font-en" dir="ltr">{{ $log->id }}</td>
                            <td><x-badge tone="info">{{ $log->event }}</x-badge></td>
                            <td class="font-en" dir="ltr">{{ $log->user?->email ?? '—' }}</td>
                            <td class="whitespace-nowrap text-steel">{{ class_basename($log->auditable_type) }} #{{ $log->auditable_id ?? '—' }}</td>
                            <td class="font-en" dir="ltr">{{ $log->ip_address ?? '—' }}</td>
                            <td class="whitespace-nowrap text-steel">{{ \App\Support\Jalali::formatInTimezone($log->created_at, config('hrm.display_timezone'), 'full') }}</td>
                            <td>
                                @if($log->old_values || $log->new_values)
                                    <details class="text-[12px]">
                                        <summary class="cursor-pointer text-steel">جزئیات</summary>
                                        <pre class="font-en mt-1 max-w-[320px] overflow-x-auto rounded bg-paper p-2 text-left" dir="ltr">{{ json_encode(['old' => $log->old_values, 'new' => $log->new_values], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}</pre>
                                    </details>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
            <div class="mt-4">{{ $logs->links() }}</div>
        @else
            <x-empty-state title="رکوردی پیدا نشد" sub="فیلترها را تغییر دهید." />
        @endif
    </x-card>
</x-layouts.app>
