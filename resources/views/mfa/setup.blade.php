<x-layouts.guest title="فعال‌سازی تأیید دومرحله‌ای">
    <ol class="mb-4 list-decimal space-y-2 pr-5 text-[13.5px] leading-7 text-steel">
        <li>یک اپ احراز هویت (مثل Google Authenticator) باز کنید.</li>
        <li>این کد را اسکن کنید یا کلید را دستی وارد کنید.</li>
        <li>کد ۶ رقمی نمایش‌داده‌شده را پایین وارد کنید.</li>
    </ol>
    {{-- RAW SVG (allowed exception — SECURITY.md §6): markup is generated
         server-side by chillerlan/php-qrcode from the otpauth URI. The
         email inside the URI is encoded into QR modules, never emitted
         as SVG text or attributes. --}}
    <div class="mb-3 flex justify-center rounded-xl border border-line bg-white p-3" dir="ltr">{!! $qrSvg !!}</div>
    <p class="field-hint mb-4 text-center">کلید دستی: <code class="font-en rounded bg-paper px-2 py-0.5" dir="ltr">{{ $secret }}</code></p>
    <form method="POST" action="{{ route('mfa.setup.confirm') }}">
        @csrf
        <x-form.input name="code" label="کد ۶ رقمی" required ltr autocomplete="one-time-code" hint="ارقام فارسی هم پذیرفته می‌شود." />
        <x-btn type="submit" tone="primary" class="w-full">تأیید و فعال‌سازی</x-btn>
    </form>
</x-layouts.guest>
