@props(['title' => '', 'actions' => ''])
<div {{ $attributes->merge(['class' => 'mb-5 rounded-xl border border-line bg-white shadow-card']) }}>
    @if($title || $actions)
        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-line px-5 py-3.5">
            @if($title)
                <h2 class="text-[15px] font-bold text-navy">{{ $title }}</h2>
            @endif
            @if($actions)
                <div class="flex flex-wrap gap-2">{{ $actions }}</div>
            @endif
        </div>
    @endif
    <div class="px-5 py-4">{{ $slot }}</div>
</div>
