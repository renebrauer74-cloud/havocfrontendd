{{-- Liste mit Zeilen (Reisegruppe, Getränke …): vorhandene Zeilen, Vorlage für neue Zeilen und Buttons.
     $presetLabel (optional) zeigt „Vorlage einfügen“ – die Zeilen dazu kommen aus window.RD_PRESETS[$list].
     $presetReplace ersetzt die Liste statt anzufügen. --}}
@php($shared = compact('list', 'canEdit', 'textMax'))
<div class="rd-list" data-rd-list="{{ $list }}">
    @foreach (array_values($rows) as $index => $row)
        @include('band-manager.riders.catering.row', $shared + ['index' => $index, 'row' => (array) $row])
    @endforeach
</div>
<p class="rd-empty" data-rd-empty="{{ $list }}" @if ($rows !== []) hidden @endif>{{ $empty }}</p>

@if ($canEdit)
    <template data-rd-template="{{ $list }}">
        @include('band-manager.riders.catering.row', $shared + ['index' => '__i__', 'row' => []])
    </template>

    <div class="d-flex flex-wrap gap-2 mt-2">
        <button type="button" class="btn btn-sm btn-outline-primary" data-rd-add="{{ $list }}"><i class="bi bi-plus-lg"></i> {{ $addLabel }}</button>
        @isset($presetLabel)
            <button type="button" class="btn btn-sm btn-outline-secondary" data-rd-preset="{{ $list }}" @if ($presetReplace ?? false) data-rd-replace @endif><i class="bi bi-magic"></i> {{ $presetLabel }}</button>
        @endisset
    </div>
@endif
