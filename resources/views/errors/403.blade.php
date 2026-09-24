<x-layouts.guest title="دسترسی محدود">
    <x-empty-state title="اجازهٔ دسترسی ندارید" sub="اگر فکر می‌کنید باید به این بخش دسترسی داشته باشید، با مدیر سیستم در میان بگذارید." icon="⛔" />
    <div class="mt-4 text-center">
        <x-btn href="{{ route('home') }}" tone="navy">بازگشت به خانه</x-btn>
    </div>
</x-layouts.guest>
