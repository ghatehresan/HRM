@props(['title' => ''])
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ? $title.' — ' : '' }}{{ config('hrm.product_name') }}</title>
    <link rel="icon" href="/brand/logo-icon.svg" type="image/svg+xml">
    <style>[x-cloak]{display:none!important}</style>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
<div x-data="{ open: false }">
    {{-- ── Sidebar (right side in RTL) ─────────────────────────── --}}
    <aside
        class="side-scroll fixed inset-y-0 right-0 z-50 flex w-[252px] translate-x-full flex-col overflow-y-auto bg-navy text-sidebar-text transition-transform tablet:translate-x-0"
        :class="{ 'translate-x-0': open }"
        aria-label="ناوبری اصلی"
    >
        <a href="{{ route('home') }}" class="flex items-center gap-3 border-b border-white/10 px-5 py-5">
            <svg viewBox="0 0 250 220" width="36" height="32" aria-hidden="true">
                <g transform="translate(5,5)">
                    <path d="M 37.7 62.5 L 120 15 L 202.3 62.5 L 202.3 157.5 L 120 205 L 37.7 157.5"
                          fill="none" stroke="#fff" stroke-width="21" stroke-linecap="round" stroke-linejoin="round"/>
                    <polygon points="150,98 78,98 78,76 20,110 78,144 78,122 150,122" fill="#F26B1D"/>
                </g>
            </svg>
            <span>
                <b class="block text-[16.5px] font-black leading-snug text-white">{{ config('hrm.company_name') }}</b>
                <em class="block text-[11.5px] not-italic leading-snug text-sidebar-dim">منابع انسانی</em>
            </span>
        </a>

        <nav class="flex-1 px-2.5 py-3">
            <a href="{{ route('home') }}"
               class="mb-0.5 flex items-center gap-3 rounded-lg px-3.5 py-2.5 text-sm font-medium transition {{ request()->routeIs('home') ? 'bg-orange font-bold text-white' : 'hover:bg-white/10 hover:text-white' }}">
                <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 12l9-9 9 9M5 10v10h14V10"/></svg>
                <span>خانه</span>
            </a>
            <a href="{{ route('health') }}"
               class="mb-0.5 flex items-center gap-3 rounded-lg px-3.5 py-2.5 text-sm font-medium transition {{ request()->routeIs('health') ? 'bg-orange font-bold text-white' : 'hover:bg-white/10 hover:text-white' }}">
                <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M22 12h-4l-3 9L9 3l-3 9H2"/></svg>
                <span>سلامت سیستم</span>
            </a>
        </nav>

        <div class="border-t border-white/10 px-4 py-3">
            <div class="text-center text-[11px] text-sidebar-dim">نسخهٔ ۰٫۱ — Milestone 1</div>
        </div>
    </aside>

    {{-- Overlay for mobile drawer --}}
    <div x-show="open" x-cloak @click="open = false" class="fixed inset-0 z-40 bg-navy/60 tablet:hidden" aria-hidden="true"></div>

    {{-- ── Mobile top bar ──────────────────────────────────────── --}}
    <div class="no-print sticky top-0 z-30 flex items-center gap-3 bg-navy px-4 py-2.5 font-bold text-white tablet:hidden">
        <button @click="open = true" aria-label="باز کردن منو"
                class="h-9 w-9 rounded-lg bg-white/10 text-lg leading-none">☰</button>
        <span>{{ $title ?: 'منابع انسانی' }}</span>
    </div>

    {{-- ── Main content ────────────────────────────────────────── --}}
    <main class="min-h-screen px-4 pb-16 pt-5 tablet:ms-[252px] tablet:px-7 tablet:pt-6">
        <x-flash />
        {{ $slot }}
    </main>
</div>
</body>
</html>
