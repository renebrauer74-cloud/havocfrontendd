{{-- Eine Zeile einer Liste im Catering Rider – auch als <template> mit $index = "__i__" für neue Zeilen --}}
@php
    $name = fn (string $field): string => $list.'['.$index.']['.$field.']';
    $value = fn (string $field): string => (string) ($row[$field] ?? '');
@endphp
<div class="rd-row" data-rd-row>
    <div class="rd-fields">
        @switch($list)
            @case('people')
                <input type="text" name="{{ $name('name') }}" value="{{ $value('name') }}" class="form-control rd-f-md" maxlength="150" placeholder="Name" aria-label="Name">
                <input type="text" name="{{ $name('role') }}" value="{{ $value('role') }}" class="form-control rd-f-md" maxlength="150" placeholder="Funktion, z. B. Gesang, Tontechnik" aria-label="Funktion">
                <input type="text" name="{{ $name('diet') }}" value="{{ $value('diet') }}" class="form-control rd-f-md" maxlength="255" placeholder="Ernährung, z. B. vegan" aria-label="Ernährung">
                <input type="text" name="{{ $name('allergy') }}" value="{{ $value('allergy') }}" class="form-control rd-f-md" maxlength="255" list="rdAllergies" placeholder="Allergien, z. B. Gluten, Sellerie" aria-label="Allergien">
                @break

            @case('drinks')
                <input type="text" name="{{ $name('item') }}" value="{{ $value('item') }}" class="form-control rd-f-lg" maxlength="255" placeholder="Getränk, z. B. Stilles Wasser 0,5 l" aria-label="Getränk">
                <input type="text" name="{{ $name('amount') }}" value="{{ $value('amount') }}" class="form-control rd-f-md" maxlength="150" placeholder="Menge, z. B. 24 Flaschen" aria-label="Menge">
                <input type="text" name="{{ $name('note') }}" value="{{ $value('note') }}" class="form-control rd-f-md" maxlength="255" placeholder="Bemerkung, z. B. gekühlt" aria-label="Bemerkung">
                @break

            @case('custom_sections')
                <div class="rd-custom">
                    <input type="text" name="{{ $name('title') }}" value="{{ $value('title') }}" class="form-control fw-semibold" maxlength="150" placeholder="Titel, z. B. Hotel" aria-label="Titel">
                    <textarea name="{{ $name('content') }}" class="form-control" rows="3" maxlength="{{ $textMax }}" placeholder="Was braucht ihr? Was muss der Veranstalter wissen?" aria-label="Inhalt">{{ $value('content') }}</textarea>
                </div>
                @break
        @endswitch
    </div>

    @if ($canEdit)
        <div class="rd-actions">
            <button type="button" class="btn btn-sm" data-rd-up title="Nach oben" aria-label="Nach oben"><i class="bi bi-arrow-up"></i></button>
            <button type="button" class="btn btn-sm" data-rd-down title="Nach unten" aria-label="Nach unten"><i class="bi bi-arrow-down"></i></button>
            <button type="button" class="btn btn-sm" data-rd-remove title="Zeile entfernen" aria-label="Zeile entfernen"><i class="bi bi-x-lg"></i></button>
        </div>
    @endif
</div>
