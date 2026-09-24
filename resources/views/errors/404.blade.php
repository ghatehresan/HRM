<x-layouts.guest title="پیدا نشد">
    <x-empty-state title="این صفحه وجود ندارد" sub="نشانی را بررسی کنید یا از منو ادامه دهید." icon="🔍" />
    <div class="mt-4 text-center">
        <x-btn href="{{ route('home') }}" tone="navy">بازگشت به خانه</x-btn>
    </div>
</x-layouts.guest>
