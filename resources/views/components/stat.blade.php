@props(['label', 'value' => '', 'hint' => '', 'tone' => ''])
@php
    $valueClass = match ($tone) {
        'neg' => 'text-danger',
        'pos' => 'text-success-d',
        default => 'text-navy',
    };
@endphp
<div {{ $attributes->merge(['class' => 'rounded-xl border border-line bg-white px-4 py-3 shadow-card']) }}>
    <div class="text-[12.5px] text-steel">{{ $label }}</div>
    <div class="mt-1 text-xl font-black {{ $valueClass }}">{{ $value ?: $slot }}</div>
    @if($hint)
        <div class="mt-0.5 text-xs text-steel">{{ $hint }}</div>
    @endif
</div>
