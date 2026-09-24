<x-layouts.guest title="کدهای بازیابی">
    <div class="mb-4 rounded-[10px] border border-warning/30 bg-warning/10 px-4 py-2.5 text-[13.5px] font-medium text-warning-d" role="alert">
        این کدها فقط همین یک‌بار نمایش داده می‌شوند. آن‌ها را یادداشت و در جای امنی نگه دارید.
    </div>
    <ol class="mb-5 grid grid-cols-1 gap-2" dir="ltr">
        @foreach($codes as $i => $code)
            <li class="font-en flex items-center gap-3 rounded-lg border border-line bg-paper px-3 py-2 text-[14px]">
                <span class="w-6 text-steel">{{ $i + 1 }}.</span>
                <span class="tracking-wider">{{ $code }}</span>
            </li>
        @endforeach
    </ol>
    <x-btn href="{{ $next }}" tone="primary" class="w-full">متوجه شدم، ادامه</x-btn>
</x-layouts.guest>
