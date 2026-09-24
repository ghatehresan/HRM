<x-layouts.guest title="نشست منقضی شد">
    <x-empty-state title="نشست شما منقضی شد" sub="صفحه را تازه کنید و دوباره تلاش کنید." icon="⏳" />
    <div class="mt-4 text-center">
        <x-btn href="{{ route('login') }}" tone="navy">ورود دوباره</x-btn>
    </div>
</x-layouts.guest>
