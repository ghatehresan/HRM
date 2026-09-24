@props(['title' => ''])
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ? $title.' — ' : '' }}{{ config('hrm.product_name') }}</title>
    <link rel="icon" href="/brand/logo-icon.svg" type="image/svg+xml">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-paper">
<div class="flex min-h-screen flex-col items-center px-4 py-10">
    <a href="{{ route('home') }}" class="mb-6 flex flex-col items-center gap-3">
        <img src="/brand/logo-horizontal.svg" alt="قطعه‌رسان" class="h-14 w-auto">
        <span class="text-[13px] font-medium text-steel">{{ config('hrm.product_name') }}</span>
    </a>
    <main class="w-full max-w-[440px]">
        <div class="rounded-2xl border border-line bg-white p-6 shadow-card tablet:p-8">
            @if($title)
                <h1 class="mb-5 text-center text-lg font-black text-navy">{{ $title }}</h1>
            @endif
            <x-flash />
            {{ $slot }}
        </div>
        <p class="mt-5 text-center text-[12px] text-steel">
            منابع انسانی قطعه‌رسان · {{ \App\Support\PersianNumbers::toFa((string) \App\Support\Jalali::today()[0]) }}
        </p>
    </main>
</div>
</body>
</html>
