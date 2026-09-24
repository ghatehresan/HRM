@props(['tone' => 'default', 'href' => null, 'type' => 'button', 'sm' => false])
@php
    $tones = [
        'default' => 'border-line bg-white text-navy hover:bg-paper',
        'primary' => 'border-orange bg-orange text-white hover:border-orange-d hover:bg-orange-d',
        'navy' => 'border-navy bg-navy text-white hover:bg-navy-2',
        'danger' => 'border-danger/30 text-danger hover:border-danger hover:bg-danger/5',
    ];
    $classes = 'inline-flex items-center justify-center gap-2 whitespace-nowrap rounded-lg border font-bold transition '
        .($sm ? 'px-3 py-1.5 text-xs ' : 'px-4 py-2 text-[13.5px] ')
        .($tones[$tone] ?? $tones['default']);
@endphp
@if($href)
    <a href="{{ $href }}" {{ $attributes->merge(['class' => $classes]) }}>{{ $slot }}</a>
@else
    <button type="{{ $type }}" {{ $attributes->merge(['class' => $classes]) }}>{{ $slot }}</button>
@endif
