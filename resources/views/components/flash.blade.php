@php
    $map = ['status' => 'success', 'success' => 'success', 'error' => 'danger', 'warning' => 'warning', 'info' => 'info'];
    $tones = [
        'success' => 'border-success/25 bg-success/10 text-success-d',
        'danger' => 'border-danger/25 bg-danger/10 text-danger',
        'warning' => 'border-warning/30 bg-warning/10 text-warning-d',
        'info' => 'border-info/25 bg-info/10 text-info',
    ];
@endphp
@foreach($map as $key => $tone)
    @if(session()->has($key))
        <div class="mb-4 rounded-[10px] border px-4 py-2.5 text-[13.5px] font-medium {{ $tones[$tone] }}" role="alert">{{ session($key) }}</div>
    @endif
@endforeach
@foreach($errors->all() as $message)
    <div class="mb-4 rounded-[10px] border border-danger/25 bg-danger/10 px-4 py-2.5 text-[13.5px] font-medium text-danger" role="alert">{{ $message }}</div>
@endforeach
