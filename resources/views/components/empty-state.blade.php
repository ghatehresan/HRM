@props(['title', 'sub' => '', 'icon' => '◻'])
<div class="rounded-xl border border-dashed border-line bg-white/60 px-6 py-10 text-center">
    <div class="text-3xl" aria-hidden="true">{{ $icon }}</div>
    <h3 class="mt-2 text-base font-bold text-navy">{{ $title }}</h3>
    @if($sub)
        <p class="mt-1 text-[13px] text-steel">{{ $sub }}</p>
    @endif
    @if(trim((string) $slot) !== '')
        <div class="mt-4">{{ $slot }}</div>
    @endif
</div>
