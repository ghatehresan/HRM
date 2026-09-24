@props(['name', 'label' => '', 'value' => '1', 'checked' => false])
<label class="mb-2 flex cursor-pointer items-center gap-2 text-[13.5px] text-ink">
    <input type="checkbox" name="{{ $name }}" value="{{ $value }}" @checked(old($name, $checked)) class="h-4 w-4 rounded border-line accent-orange">
    <span>{{ $label }}</span>
</label>
