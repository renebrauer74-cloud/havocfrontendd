@php
    use App\Enums\BandManager\StagePlotElementEnum;

    $groups = collect($elements)->groupBy(fn (StagePlotElementEnum $element): string => $element->group());
    // Nach einem Fehler kommen die Elemente als JSON-Text zurück
    $oldItems = old('items');
    $config = [
        'band' => $band->name,
        'readonly' => ! $canEdit,
        'maxItems' => $maxItems,
        'minStage' => $minStage,
        'maxStage' => $maxStage,
        'items' => array_values(is_string($oldItems) ? (array) json_decode($oldItems, true) : (array) ($oldItems ?? $plot->items ?? [])),
        'templates' => $templates,
        'elements' => collect($elements)->mapWithKeys(fn (StagePlotElementEnum $element): array => [
            $element->value => ['label' => $element->label(), 'w' => $element->size()[0], 'h' => $element->size()[1]],
        ]),
    ];
@endphp
<x-band-manager.layout :title="'Stage Plot – '.$band->name">
    @push('styles')
        <link href="{{ asset('assets/band-manager/css/riders.css') }}?v=4" rel="stylesheet">
    @endpush

    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <div>
            @if ($bands->count() > 1)<a href="{{ route('band-manager.stage-plots.index') }}" class="hv-back"><i class="bi bi-arrow-left"></i> Alle Bands</a>@endif
            <h1 class="h3 mb-0">{{ $band->name }} – Stage Plot</h1>
            <div class="small text-muted">
                @if ($plot->exists)
                    Stand {{ $plot->updated_at->format('d.m.Y, H:i') }} Uhr
                @else
                    Noch nicht gespeichert
                @endif
            </div>
        </div>
        <div class="d-flex flex-wrap align-items-center gap-2">
            @if ($bands->count() > 1)
                <x-band-manager.band-switcher :bands="$bands" :current="$band" route="band-manager.stage-plots.band" />
            @endif
            @if ($hasImage)
                <a href="{{ route('band-manager.stage-plots.image', ['band' => $band, 'download' => 1]) }}" class="btn btn-outline-secondary" data-sp-download><i class="bi bi-image"></i> PNG</a>
                <a href="{{ route('band-manager.stage-plots.pdf', $band) }}" class="btn btn-primary" data-sp-download><i class="bi bi-download"></i> Stage Plot herunterladen (PDF)</a>
            @endif
        </div>
    </div>

    @if ($canEdit && ! $plot->exists)
        <div class="alert alert-info hv-flash">
            <i class="bi bi-lightbulb"></i>
            <span>
                <strong>So geht's:</strong> Die Bühne seht ihr von oben, das Publikum ist unten.
                Klickt links auf ein Symbol (oder zieht es auf die Bühne) und schiebt es mit der Maus an seinen Platz.
                Unter der Bühne gebt ihr dem markierten Element einen Namen, dreht es oder ändert die Größe.
                Mit <strong>„Vorlage: Rockband“</strong> habt ihr sofort einen Startpunkt. Nach dem Speichern gibt es den Plot als <strong>PDF und PNG</strong>.
            </span>
        </div>
    @endif

    <form method="post" action="{{ route('band-manager.stage-plots.update', $band) }}" id="spForm" @class(['sp-form', 'sp-readonly' => ! $canEdit])>
        @csrf
        @method('PUT')
        <input type="hidden" name="items" id="spItems" value="{{ json_encode($config['items']) }}">
        <input type="hidden" name="image" id="spImage" value="">

        <fieldset @disabled(! $canEdit)>
            <div class="sp-designer">
                @if ($canEdit)
                    <aside class="card card-body shadow-sm sp-palette" aria-label="Symbole">
                        <button type="button" class="btn btn-sm btn-outline-primary w-100" data-sp-template="rock"><i class="bi bi-magic"></i> Vorlage: Rockband</button>
                        @foreach ($groups as $group => $groupElements)
                            <div class="sp-group-title">{{ $group }}</div>
                            <div class="sp-tools">
                                @foreach ($groupElements as $element)
                                    <button type="button" class="sp-tool" draggable="true" data-sp-add="{{ $element->value }}" title="{{ $element->label() }} hinzufügen">
                                        @if ($element === StagePlotElementEnum::Text)
                                            <i class="bi bi-fonts" aria-hidden="true"></i>
                                        @else
                                            <svg aria-hidden="true"><use href="#sp-{{ $element->value }}"/></svg>
                                        @endif
                                        <span>{{ $element->label() }}</span>
                                    </button>
                                @endforeach
                            </div>
                        @endforeach
                    </aside>
                @endif

                <section class="card card-body shadow-sm sp-main">
                    <div class="sp-toolbar">
                        @foreach (['stage_width' => 'Bühnenbreite', 'stage_depth' => 'Bühnentiefe'] as $field => $label)
                            <div>
                                <label class="form-label small mb-1" for="{{ $field }}">{{ $label }}</label>
                                <div class="input-group input-group-sm">
                                    <input type="number" name="{{ $field }}" id="{{ $field }}" class="form-control" min="{{ $minStage }}" max="{{ $maxStage }}" step="0.5" required
                                           value="{{ old($field, (float) $plot->{$field}) }}" data-sp-stage>
                                    <span class="input-group-text">m</span>
                                </div>
                            </div>
                        @endforeach
                        <div class="form-check form-switch mb-1">
                            <input class="form-check-input" type="checkbox" id="spGrid" checked>
                            <label class="form-check-label small" for="spGrid">Raster (1 m)</label>
                        </div>
                        @if ($canEdit)
                            <button type="button" class="btn btn-sm btn-outline-secondary ms-auto" data-sp-clear><i class="bi bi-trash3"></i> Alles entfernen</button>
                        @endif
                    </div>

                    <div class="sp-canvas">
                        <svg id="spStage" xmlns="http://www.w3.org/2000/svg" role="img" aria-label="Stage Plot {{ $band->name }}" tabindex="0">
                            <defs>@include('band-manager.riders.stage-plot.symbols')</defs>
                        </svg>
                    </div>

                    @if ($canEdit)
                        <p class="sp-hint mb-0">
                            <i class="bi bi-info-circle"></i>
                            Ziehen verschiebt · Doppelklick beschriftet · Pfeiltasten schieben fein · <kbd>R</kbd> dreht · <kbd>Entf</kbd> löscht · <kbd>Esc</kbd> hebt die Auswahl auf
                        </p>

                        <div class="sp-props" id="spProps" hidden>
                            <div class="sp-props-title"><i class="bi bi-cursor"></i> <span id="spPropTitle"></span></div>
                            <div class="sp-prop-label">
                                <label class="form-label small mb-1" for="spLabel" id="spLabelCaption">Beschriftung</label>
                                <input type="text" id="spLabel" class="form-control form-control-sm" maxlength="80" placeholder="z. B. Lena – Vocals, Mon 1 …">
                            </div>
                            <div>
                                <label class="form-label small mb-1" for="spRotation">Drehung <output id="spRotationOut"></output></label>
                                <div class="d-flex align-items-center gap-1">
                                    <button type="button" class="btn btn-sm btn-outline-secondary" data-sp-rotate="-45" title="45° nach links"><i class="bi bi-arrow-counterclockwise"></i></button>
                                    <input type="range" id="spRotation" class="form-range" min="-180" max="180" step="5">
                                    <button type="button" class="btn btn-sm btn-outline-secondary" data-sp-rotate="45" title="45° nach rechts"><i class="bi bi-arrow-clockwise"></i></button>
                                </div>
                            </div>
                            <div data-sp-size="scale">
                                <label class="form-label small mb-1" for="spScale">Größe <output id="spScaleOut"></output></label>
                                <input type="range" id="spScale" class="form-range" min="50" max="300" step="10">
                            </div>
                            <div data-sp-size="font">
                                <label class="form-label small mb-1" for="spFont">Schriftgröße <output id="spFontOut"></output></label>
                                <input type="range" id="spFont" class="form-range" min="12" max="80" step="2">
                            </div>
                            <div data-sp-size="riser" class="d-flex gap-2">
                                @foreach (['spRiserW' => 'Breite', 'spRiserD' => 'Tiefe'] as $id => $label)
                                    <div>
                                        <label class="form-label small mb-1" for="{{ $id }}">{{ $label }}</label>
                                        <div class="input-group input-group-sm sp-riser-size">
                                            <input type="number" id="{{ $id }}" class="form-control" min="0.5" max="{{ $maxStage }}" step="0.5">
                                            <span class="input-group-text">m</span>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                            <div class="sp-prop-actions">
                                <button type="button" class="btn btn-sm btn-outline-secondary" data-sp-action="duplicate" title="Duplizieren (Strg+D)"><i class="bi bi-copy"></i></button>
                                <button type="button" class="btn btn-sm btn-outline-secondary" data-sp-action="front" title="Nach vorne holen"><i class="bi bi-front"></i></button>
                                <button type="button" class="btn btn-sm btn-outline-secondary" data-sp-action="back" title="Nach hinten legen"><i class="bi bi-back"></i></button>
                                <button type="button" class="btn btn-sm btn-outline-danger" data-sp-action="delete" title="Entfernen (Entf)"><i class="bi bi-trash3"></i></button>
                            </div>
                        </div>
                    @endif

                    <div class="row g-3 mt-1">
                        <x-band-manager.textarea name="notes" label="Hinweise zum Bühnenplan" :value="$plot->notes" :max="$textMax" rows="3"
                                                 placeholder="z. B. Drum-Riser 3 × 2 m, 40 cm hoch · Strom an jeder Position · Keyboard auf eigenem Ständer …" />
                    </div>
                </section>
            </div>
        </fieldset>

        @if ($canEdit)
            <div class="rd-savebar">
                <span class="rd-dirty small" id="spDirty" hidden><i class="bi bi-circle-fill"></i> Ungespeicherte Änderungen</span>
                <button class="btn btn-primary" id="spSave"><i class="bi bi-check-lg"></i> Speichern</button>
            </div>
        @endif
    </form>

    @push('scripts')
        <script>window.SP_CONFIG = @json($config);</script>
        <script src="{{ asset('assets/band-manager/js/stage-plot.js') }}?v=2"></script>
    @endpush
</x-band-manager.layout>
