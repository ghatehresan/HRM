@props(['name', 'label' => '', 'type' => 'text', 'value' => '', 'required' => false, 'ltr' => false, 'hint' => '', 'autocomplete' => ''])
@php($id = 'f-'.$name)
<div class="mb-4">
    @if($label)
        <label for="{{ $id }}" class="field-label">{{ $label }} @if($required)<span class="text-danger">*</span>@endif</label>
    @endif
    <input id="{{ $id }}" name="{{ $name }}" type="{{ $type }}" value="{{ old($name, $value) }}"
        @if($required) required @endif
        @if($autocomplete) autocomplete="{{ $autocomplete }}" @endif
        {{ $attributes->merge(['class' => 'field-input'.($ltr ? ' ltr-input' : '').($errors->has($name) ? ' field-input-error' : '')]) }}>
    @if($hint && !$errors->has($name))
        <p class="field-hint">{{ $hint }}</p>
    @endif
    @error($name)
        <p class="field-error">{{ $message }}</p>
    @enderror
</div>
