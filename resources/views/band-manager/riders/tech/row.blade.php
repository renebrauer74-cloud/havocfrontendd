{{-- Eine Zeile einer Liste im Tech Rider – auch als <template> mit $index = "__i__" für neue Zeilen --}}
@php
    $name = fn (string $field): string => $list.'['.$index.']['.$field.']';
    $value = fn (string $field): string => (string) ($row[$field] ?? '');
@endphp
<div class="rd-row" data-rd-row>
    @if (in_array($list, ['inputs', 'monitors'], true))
        <span class="rd-num" data-rd-num></span>
    @endif

    <div class="rd-fields">
        @switch($list)
            @case('contacts')
                <input type="text" name="{{ $name('role') }}" value="{{ $value('role') }}" class="form-control rd-f-md" maxlength="150" list="rdContactRoles" placeholder="Bereich, z. B. Lichttechnik" aria-label="Bereich">
                <input type="text" name="{{ $name('name') }}" value="{{ $value('name') }}" class="form-control rd-f-md" maxlength="150" placeholder="Name" aria-label="Name">
                <input type="tel" name="{{ $name('phone') }}" value="{{ $value('phone') }}" class="form-control rd-f-md" maxlength="50" placeholder="Telefon" aria-label="Telefon">
                <input type="email" name="{{ $name('email') }}" value="{{ $value('email') }}" class="form-control rd-f-md" maxlength="190" placeholder="E-Mail" aria-label="E-Mail">
                @break

            @case('line_up')
                <input type="text" name="{{ $name('name') }}" value="{{ $value('name') }}" class="form-control rd-f-lg" maxlength="150" placeholder="Name" aria-label="Name">
                <input type="text" name="{{ $name('instrument') }}" value="{{ $value('instrument') }}" class="form-control rd-f-lg" maxlength="150" placeholder="Instrument / Funktion, z. B. Gesang, Gitarre" aria-label="Instrument">
                @break

            @case('inputs')
                <input type="text" name="{{ $name('source') }}" value="{{ $value('source') }}" class="form-control rd-f-lg" maxlength="150" placeholder="Instrument / Quelle, z. B. Snare Top" aria-label="Instrument / Quelle">
                <input type="text" name="{{ $name('mic') }}" value="{{ $value('mic') }}" class="form-control rd-f-md" maxlength="150" placeholder="Mikrofon / DI, z. B. SM57" aria-label="Mikrofon / DI">
                <select name="{{ $name('stand') }}" class="form-select rd-f-md" aria-label="Stativ">
                    <option value="">Stativ: –</option>
                    @foreach ($stands as $stand)
                        <option value="{{ $stand->value }}" @selected($value('stand') === $stand->value)>{{ $stand->label() }}</option>
                    @endforeach
                </select>
                <label class="rd-phantom" title="Phantomspeisung (+48 V) nötig">
                    <input type="hidden" name="{{ $name('phantom') }}" value="0">
                    <input type="checkbox" name="{{ $name('phantom') }}" value="1" class="form-check-input" @checked(filter_var($row['phantom'] ?? false, FILTER_VALIDATE_BOOLEAN))>
                    <span>+48 V</span>
                </label>
                <input type="text" name="{{ $name('note') }}" value="{{ $value('note') }}" class="form-control rd-f-md" maxlength="255" placeholder="Bemerkung" aria-label="Bemerkung">
                @break

            @case('monitors')
                <input type="text" name="{{ $name('who') }}" value="{{ $value('who') }}" class="form-control rd-f-md" maxlength="150" placeholder="Für wen, z. B. Lead Vocals" aria-label="Für wen">
                <select name="{{ $name('type') }}" class="form-select rd-f-md" aria-label="Art">
                    <option value="">Art: –</option>
                    @foreach ($monitorTypes as $type)
                        <option value="{{ $type->value }}" @selected($value('type') === $type->value)>{{ $type->label() }}</option>
                    @endforeach
                </select>
                <input type="text" name="{{ $name('note') }}" value="{{ $value('note') }}" class="form-control rd-f-lg" maxlength="255" placeholder="Was soll drauf? z. B. viel Gesang, etwas Kick" aria-label="Mix-Wunsch">
                @break

            @case('backline')
                <input type="text" name="{{ $name('item') }}" value="{{ $value('item') }}" class="form-control rd-f-lg" maxlength="255" placeholder="Gerät, z. B. Bass-Amp Ampeg SVT + 8x10" aria-label="Gerät">
                <select name="{{ $name('provider') }}" class="form-select rd-f-md" aria-label="Wer stellt?">
                    <option value="">Wer stellt? –</option>
                    @foreach ($providers as $provider)
                        <option value="{{ $provider->value }}" @selected($value('provider') === $provider->value)>{{ $provider->label() }}</option>
                    @endforeach
                </select>
                <input type="text" name="{{ $name('note') }}" value="{{ $value('note') }}" class="form-control rd-f-md" maxlength="255" placeholder="Bemerkung" aria-label="Bemerkung">
                @break

            @case('custom_sections')
                <div class="rd-custom">
                    <input type="text" name="{{ $name('title') }}" value="{{ $value('title') }}" class="form-control fw-semibold" maxlength="150" placeholder="Titel, z. B. Pyro / Effekte" aria-label="Titel">
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
