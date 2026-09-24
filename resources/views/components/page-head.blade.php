@props(['title', 'sub' => '', 'actions' => ''])
<div class="mb-6 flex flex-wrap items-start justify-between gap-4">
    <div>
        <h1 class="text-[25px] font-black leading-snug text-navy">{{ $title }}</h1>
        @if($sub)
            <p class="mt-0.5 text-[13.5px] text-steel">{{ $sub }}</p>
        @endif
    </div>
    @if($actions)
        <div class="flex flex-wrap gap-2">{{ $actions }}</div>
    @endif
</div>
