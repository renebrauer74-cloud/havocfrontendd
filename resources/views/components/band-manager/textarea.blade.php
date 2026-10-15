{{-- Mehrzeiliges Feld mit Formatier-Legende (data-md: **fett**, *kursiv* …) --}}
@props(['name', 'label', 'value' => null, 'col' => 'col-12', 'placeholder' => '', 'max' => 2000, 'rows' => 2])
<div class="{{ $col }}">
    <label class="form-label" for="{{ $name }}">{{ $label }}</label>
    <textarea name="{{ $name }}" id="{{ $name }}" rows="{{ $rows }}" maxlength="{{ $max }}" placeholder="{{ $placeholder }}" data-md
              {{ $attributes->merge(['class' => 'form-control']) }}>{{ old($name, $value) }}</textarea>
</div>
