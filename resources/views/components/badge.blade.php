@props(['tone' => 'gray'])
@php
    $tones = [
        'green' => 'bg-success/10 text-success-d',
        'red' => 'bg-danger/10 text-danger',
        'amber' => 'bg-warning/15 text-warning-d',
        'info' => 'bg-info/10 text-info',
        'navy' => 'bg-navy/10 text-navy',
        'orange' => 'bg-orange/10 text-orange-d',
        'gray' => 'bg-steel/10 text-steel',
    ];
@endphp
<span {{ $attributes->merge(['class' => 'inline-block rounded-[5px] px-2.5 py-0.5 text-xs font-bold '.($tones[$tone] ?? $tones['gray'])]) }}>{{ $slot }}</span>
