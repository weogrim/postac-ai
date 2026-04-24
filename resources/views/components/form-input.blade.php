@props([
    'name',
    'label',
    'type' => 'text',
    'value' => null,
    'autocomplete' => null,
    'required' => false,
    'autofocus' => false,
    'hint' => null,
])

@php
    $hasError = $errors->has($name);
    $inputId = $attributes->get('id') ?? 'input-'.$name;
@endphp

<fieldset class="fieldset">
    <label for="{{ $inputId }}" class="fieldset-legend">{{ $label }}</label>
    <input
        {{ $attributes->class([
            'input w-full',
            'input-error' => $hasError,
        ]) }}
        id="{{ $inputId }}"
        name="{{ $name }}"
        type="{{ $type }}"
        value="{{ old($name, $value) }}"
        @if ($autocomplete) autocomplete="{{ $autocomplete }}" @endif
        @if ($required) required @endif
        @if ($autofocus) autofocus @endif
    />
    @if ($hint && ! $hasError)
        <p class="fieldset-label">{{ $hint }}</p>
    @endif
    @error($name)
        <p class="fieldset-label text-error">{{ $message }}</p>
    @enderror
</fieldset>
