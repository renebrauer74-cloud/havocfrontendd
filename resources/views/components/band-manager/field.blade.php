{{-- Einzeiliges Formularfeld mit Label – Wert aus old() oder dem Model --}}
@props(['name', 'label', 'value' => null, 'col' => 'col-md-6', 'type' => 'text', 'placeholder' => '', 'max' => 150])
@php($id = $attributes->get('id', $name))
<div class="{{ $col }}">
    <label class="form-label" for="{{ $id }}">{{ $label }}</label>
    <input type="{{ $type }}" name="{{ $name }}" id="{{ $id }}" maxlength="{{ $max }}" placeholder="{{ $placeholder }}"
           value="{{ old($name, $value) }}" {{ $attributes->except('id')->merge(['class' => 'form-control']) }}>
</div>
