@props(['name', 'label' => '', 'options' => [], 'value' => null, 'required' => false, 'hint' => ''])
@php($id = 'f-'.$name)
<div class="mb-4">
    @if($label)
        <label for="{{ $id }}" class="field-label">{{ $label }} @if($required)<span class="text-danger">*</span>@endif</label>
    @endif
    <select id="{{ $id }}" name="{{ $name }}"
        @if($required) required @endif
        {{ $attributes->merge(['class' => 'field-input'.($errors->has($name) ? ' field-input-error' : '')]) }}>
        @foreach($options as $optValue => $optLabel)
            <option value="{{ $optValue }}" @selected((string) old($name, $value) === (string) $optValue)>{{ $optLabel }}</option>
        @endforeach
    </select>
    @if($hint && !$errors->has($name))
        <p class="field-hint">{{ $hint }}</p>
    @endif
    @error($name)
        <p class="field-error">{{ $message }}</p>
    @enderror
</div>
