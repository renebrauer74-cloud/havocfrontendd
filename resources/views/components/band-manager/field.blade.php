{{-- Einzeiliges Formularfeld mit Label – Wert aus old() oder dem Model --}}
@props(['name', 'label', 'value' => null, 'col' => 'col-md-6', 'type' => 'text', 'placeholder' => '', 'max' => 150])
<div class="{{ $col }}">
    <label class="form-label" for="{{ $name }}">{{ $label }}</label>
    <input type="{{ $type }}" name="{{ $name }}" id="{{ $name }}" maxlength="{{ $max }}" placeholder="{{ $placeholder }}"
           value="{{ old($name, $value) }}" {{ $attributes->merge(['class' => 'form-control']) }}>
</div>
