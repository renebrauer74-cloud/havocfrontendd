@php
    use App\Enums\BandManager\CateringBuyoutEnum;
    use App\Enums\BandManager\CateringMealEnum;

    $listOptions = compact('canEdit', 'textMax');
    $rows = fn (string $list, ?array $fallback = null): array => array_values((array) old($list, $rider->exists ? ($rider->{$list} ?? []) : ($fallback ?? [])));
    $presets = [
        'people' => $suggestedPeople,
        'drinks' => $templates['drinks'] ?? [],
    ];
    // Neue Rider: Snacks bei Ankunft und warmes Abendessen sind vorausgewählt
    $meal = fn (CateringMealEnum $meal): array => (array) old('meals.'.$meal->value, $rider->meals[$meal->value]
        ?? ['wanted' => ! $rider->exists && in_array($meal, [CateringMealEnum::Arrival, CateringMealEnum::Dinner], true)]);
    $buyout = old('buyout', $rider->buyout?->value);
    $hasBuyout = (bool) CateringBuyoutEnum::tryFrom((string) $buyout)?->hasBuyout();
    $sections = [
        'contact' => ['person-badge', 'Kontakt'],
        'people' => ['people', 'Reisegruppe & Ernährung'],
        'meals' => ['egg-fried', 'Mahlzeiten'],
        'buyout' => ['cash-coin', 'Buyout'],
        'drinks' => ['cup-straw', 'Getränke'],
        'dressing' => ['door-closed', 'Garderobe'],
        'custom' => ['plus-square', 'Eigene Abschnitte'],
        'notes' => ['chat-left-text', 'Sonstiges'],
    ];
    $counts = [
        'dressing_rooms' => ['Garderoben', 'Räume'],
        'stage_towels' => ['Bühnenhandtücher', 'Stück'],
        'shower_towels' => ['Duschhandtücher', 'Stück'],
    ];
@endphp
<x-band-manager.layout :title="'Catering Rider – '.$band->name">
    @push('styles')
        <link href="{{ asset('assets/band-manager/css/riders.css') }}?v=3" rel="stylesheet">
    @endpush

    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <div>
            @if ($bands->count() > 1)<a href="{{ route('band-manager.catering-riders.index') }}" class="hv-back"><i class="bi bi-arrow-left"></i> Alle Bands</a>@endif
            <h1 class="h3 mb-0">{{ $band->name }} – Catering Rider</h1>
            <div class="small text-muted">
                @if ($rider->exists)
                    Stand {{ $rider->updated_at->format('d.m.Y, H:i') }} Uhr
                @else
                    Noch nicht gespeichert
                @endif
            </div>
        </div>
        <div class="d-flex flex-wrap align-items-center gap-2">
            @if ($bands->count() > 1)
                <x-band-manager.band-switcher :bands="$bands" :current="$band" route="band-manager.catering-riders.band" />
            @endif
            @if ($rider->exists)
                <a href="{{ route('band-manager.catering-riders.pdf', $band) }}" class="btn btn-primary" data-rd-pdf><i class="bi bi-download"></i> Fertigen Rider herunterladen (PDF)</a>
            @endif
        </div>
    </div>

    @if ($canEdit && ! $rider->exists)
        <div class="alert alert-info hv-flash">
            <i class="bi bi-lightbulb"></i>
            <span>
                <strong>So geht's:</strong> Die Reisegruppe mit Ernährung und Allergien haben wir aus „Band/Team“ übernommen – prüft sie kurz.
                Dann wählt ihr die Mahlzeiten, ob ihr lieber einen <strong>Buyout</strong> möchtet, und eure Getränke (mit <strong>„Vorlage einfügen“</strong> geht's schneller).
                Alles andere einfach leer lassen. Nach dem Speichern könnt ihr den Rider als <strong>PDF herunterladen</strong>.
            </span>
        </div>
    @endif

    <nav class="rd-jump" aria-label="Abschnitte">
        @foreach ($sections as $anchor => [$icon, $label])
            <a href="#rd-{{ $anchor }}"><i class="bi bi-{{ $icon }}"></i> {{ $label }}</a>
        @endforeach
    </nav>

    <form method="post" action="{{ route('band-manager.catering-riders.update', $band) }}" id="rdForm" class="rd-form">
        @csrf
        @method('PUT')

        <fieldset @disabled(! $canEdit)>

            {{-- ================= Kontakt ================= --}}
            <section class="card card-body shadow-sm rd-card" id="rd-contact">
                <h2 class="rd-title"><i class="bi bi-person-badge"></i> Ansprechpartner</h2>
                <p class="rd-help">Wen kann der Veranstalter zu Catering und Garderobe erreichen – meist der Tour Manager?</p>
                <div class="row g-3">
                    <x-band-manager.field name="contact_name" label="Name" :value="$rider->contact_name" col="col-md-4" />
                    <x-band-manager.field name="contact_phone" label="Telefon" :value="$rider->contact_phone" col="col-md-4" type="tel" :max="50" />
                    <x-band-manager.field name="contact_email" label="E-Mail" :value="$rider->contact_email" col="col-md-4" type="email" :max="190" />
                </div>
            </section>

            {{-- ================= Reisegruppe & Ernährung ================= --}}
            <section class="card card-body shadow-sm rd-card" id="rd-people">
                <h2 class="rd-title"><i class="bi bi-people"></i> Reisegruppe &amp; Ernährung</h2>
                <p class="rd-help">
                    Wer isst mit? Ernährung und Allergie kommen automatisch aus den Steckbriefen unter „Band/Team“ – leere Felder werden von dort ergänzt.
                    Ihr könnt hier weitere Allergien dazuschreiben (mehrere mit Komma trennen). Beide Felder leer = isst alles.
                    Die Küche bekommt im PDF zusätzlich eine Übersicht („3 × vegan, 1 × Gluten …“).
                </p>
                @include('band-manager.riders.catering.list', $listOptions + [
                    'list' => 'people',
                    'rows' => array_values((array) old('people', $people)),
                    'addLabel' => 'Person',
                    'empty' => 'Noch niemand eingetragen.',
                    'presetLabel' => $suggestedPeople !== [] ? 'Neu aus „Band/Team“ übernehmen' : null,
                    'presetReplace' => true,
                ])
                <datalist id="rdAllergies">
                    @foreach ($allergies as $allergy)
                        <option value="{{ $allergy->label() }}">
                    @endforeach
                </datalist>
                <div class="row g-3 mt-1">
                    <x-band-manager.textarea name="diet_notes" label="Allgemeine Hinweise zur Ernährung" :value="$rider->diet_notes" :max="$textMax" rows="2"
                                             placeholder="z. B. Bitte Allergene kennzeichnen · keine Nüsse in der Küche (schwere Allergie) · Halal-Option für eine Person …" />
                </div>
            </section>

            {{-- ================= Mahlzeiten ================= --}}
            <section class="card card-body shadow-sm rd-card" id="rd-meals">
                <h2 class="rd-title"><i class="bi bi-egg-fried"></i> Mahlzeiten</h2>
                <p class="rd-help">Hakt an, was ihr am Show-Tag braucht, und schreibt dazu, wann und was ihr euch wünscht.</p>
                <div class="rd-meals">
                    @foreach ($meals as $mealCase)
                        @php
                            $values = $meal($mealCase);
                            $isWanted = filter_var($values['wanted'] ?? false, FILTER_VALIDATE_BOOLEAN);
                            $field = 'meals['.$mealCase->value.']';
                        @endphp
                        <div @class(['rd-meal', 'is-off' => ! $isWanted]) data-rd-meal>
                            <div class="rd-meal-head">
                                <div class="form-check">
                                    <input type="hidden" name="{{ $field }}[wanted]" value="0">
                                    <input class="form-check-input" type="checkbox" name="{{ $field }}[wanted]" value="1" id="meal_{{ $mealCase->value }}" @checked($isWanted)>
                                    <label class="form-check-label" for="meal_{{ $mealCase->value }}"><i class="bi bi-{{ $mealCase->icon() }}"></i>{{ $mealCase->label() }}</label>
                                </div>
                                <input type="text" name="{{ $field }}[time]" value="{{ $values['time'] ?? '' }}" class="form-control" maxlength="150"
                                       placeholder="Wann? {{ $mealCase->timeHint() }}" aria-label="Zeitpunkt {{ $mealCase->label() }}">
                            </div>
                            <textarea name="{{ $field }}[wishes]" class="form-control" rows="2" maxlength="{{ $textMax }}"
                                      placeholder="Wünsche, {{ $mealCase->wishesHint() }}" aria-label="Wünsche {{ $mealCase->label() }}">{{ $values['wishes'] ?? '' }}</textarea>
                        </div>
                    @endforeach
                </div>
            </section>

            {{-- ================= Buyout ================= --}}
            <section class="card card-body shadow-sm rd-card" id="rd-buyout">
                <h2 class="rd-title"><i class="bi bi-cash-coin"></i> Catering oder Buyout</h2>
                <p class="rd-help">Beim Buyout zahlt der Veranstalter statt einer Mahlzeit einen festen Betrag pro Person – üblich sind 10–30 € pro Person und Show.</p>
                <div class="rd-options mb-3">
                    @foreach ($buyoutOptions as $option)
                        <label class="rd-option">
                            <input class="form-check-input" type="radio" name="buyout" value="{{ $option->value }}" data-rd-buyout="{{ $option->hasBuyout() ? 1 : 0 }}" @checked($buyout === $option->value)>
                            <span><strong>{{ $option->label() }}</strong><span>{{ $option->description() }}</span></span>
                        </label>
                    @endforeach
                </div>
                <div class="row g-3" id="rdBuyoutFields" @if (! $hasBuyout) hidden @endif>
                    <div class="col-sm-6 col-md-3">
                        <label class="form-label" for="buyout_amount">Buyout pro Person</label>
                        <div class="input-group">
                            <input type="number" name="buyout_amount" id="buyout_amount" class="form-control" min="0" max="{{ $maxBuyout }}" step="0.5" value="{{ old('buyout_amount', $rider->buyout_amount) }}">
                            <span class="input-group-text">€</span>
                        </div>
                    </div>
                    <x-band-manager.textarea name="buyout_notes" label="Hinweise zum Buyout" :value="$rider->buyout_notes" :max="$textMax" rows="2" col="col-md-9"
                                             placeholder="z. B. bar bei Ankunft an den Tour Manager · gilt für das Abendessen, Snacks und Getränke bitte trotzdem …" />
                </div>
            </section>

            {{-- ================= Getränke ================= --}}
            <section class="card card-body shadow-sm rd-card" id="rd-drinks">
                <h2 class="rd-title"><i class="bi bi-cup-straw"></i> Getränke</h2>
                <p class="rd-help">Was soll in der Garderobe und auf der Bühne bereitstehen – mit Menge pro Show?</p>
                @include('band-manager.riders.catering.list', $listOptions + [
                    'list' => 'drinks',
                    'rows' => $rows('drinks'),
                    'addLabel' => 'Getränk',
                    'empty' => 'Noch keine Getränke – fügt eins hinzu oder nutzt die Vorlage.',
                    'presetLabel' => 'Vorlage einfügen',
                ])
                <div class="row g-3 mt-1">
                    <x-band-manager.textarea name="drink_notes" label="Weitere Hinweise zu Getränken" :value="$rider->drink_notes" :max="$textMax" rows="2"
                                             placeholder="z. B. Getränke-Bons für die Bar, falls nicht alles gestellt werden kann · bitte kein Plastikgeschirr …" />
                </div>
            </section>

            {{-- ================= Garderobe ================= --}}
            <section class="card card-body shadow-sm rd-card" id="rd-dressing">
                <h2 class="rd-title"><i class="bi bi-door-closed"></i> Garderobe &amp; Backstage</h2>
                <p class="rd-help">Wie viele Räume und Handtücher braucht ihr – und was soll in der Garderobe sein?</p>
                <div class="row g-3">
                    @foreach ($counts as $field => [$label, $unit])
                        <div class="col-6 col-md-3">
                            <label class="form-label" for="{{ $field }}">{{ $label }}</label>
                            <div class="input-group">
                                <input type="number" name="{{ $field }}" id="{{ $field }}" class="form-control" min="0" max="255" step="1" value="{{ old($field, $rider->{$field}) }}">
                                <span class="input-group-text">{{ $unit }}</span>
                            </div>
                        </div>
                    @endforeach
                    <div class="col-6 col-md-3 d-flex align-items-end">
                        <div class="form-check mb-2">
                            <input type="hidden" name="needs_shower" value="0">
                            <input class="form-check-input" type="checkbox" name="needs_shower" value="1" id="needs_shower" @checked(old('needs_shower', $rider->needs_shower))>
                            <label class="form-check-label" for="needs_shower">Dusche benötigt</label>
                        </div>
                    </div>
                    <x-band-manager.textarea name="dressing_room_notes" label="Ausstattung der Garderobe" :value="$rider->dressing_room_notes" :max="$textMax" rows="3"
                                             placeholder="z. B. abschließbar, beheizt, Spiegel, Sitzplätze für 6 Personen, Kleiderstange, Steckdosen, Kühlschrank, WC in der Nähe …" />
                </div>
            </section>

            {{-- ================= Eigene Abschnitte ================= --}}
            <section class="card card-body shadow-sm rd-card" id="rd-custom">
                <h2 class="rd-title"><i class="bi bi-plus-square"></i> Eigene Abschnitte</h2>
                <p class="rd-help">Alles, was oben keinen Platz hat – legt dafür einfach einen eigenen Abschnitt mit Titel an.</p>
                @include('band-manager.riders.catering.list', $listOptions + [
                    'list' => 'custom_sections',
                    'rows' => $rows('custom_sections'),
                    'addLabel' => 'Eigener Abschnitt',
                    'empty' => 'Noch keine eigenen Abschnitte.',
                ])
                @if ($canEdit && ($templates['section_ideas'] ?? []) !== [])
                    <div class="rd-ideas">
                        <span class="small text-muted">Ideen:</span>
                        @foreach ($templates['section_ideas'] as $idea)
                            <button type="button" class="rd-idea" data-rd-idea="{{ $idea }}"><i class="bi bi-plus"></i> {{ $idea }}</button>
                        @endforeach
                    </div>
                @endif
            </section>

            {{-- ================= Sonstiges ================= --}}
            <section class="card card-body shadow-sm rd-card" id="rd-notes">
                <h2 class="rd-title"><i class="bi bi-chat-left-text"></i> Sonstiges</h2>
                <div class="row g-3">
                    <x-band-manager.textarea name="notes" label="Weitere Hinweise an den Veranstalter" :value="$rider->notes" :max="$textMax" rows="3"
                                             placeholder="z. B. Abweichungen bitte vorher absprechen · Rückmeldung bis 14 Tage vor der Show …" />
                </div>
            </section>

        </fieldset>

        @if ($canEdit)
            <div class="rd-savebar">
                <span class="rd-dirty small" id="rdDirty" hidden><i class="bi bi-circle-fill"></i> Ungespeicherte Änderungen</span>
                @if ($rider->exists)
                    <a href="{{ route('band-manager.catering-riders.pdf', $band) }}" class="btn btn-outline-secondary" data-rd-pdf><i class="bi bi-download"></i> PDF</a>
                @endif
                <button class="btn btn-primary"><i class="bi bi-check-lg"></i> Speichern</button>
            </div>
        @endif
    </form>

    @push('scripts')
        <script>window.RD_PRESETS = @json($presets);</script>
        <script src="{{ asset('assets/band-manager/js/tech-rider.js') }}?v=4"></script>
        <script>
            // Mahlzeiten ein-/ausklappen, Buyout-Betrag nur bei Buyout zeigen
            document.querySelectorAll('[data-rd-meal]').forEach(meal => {
                meal.querySelector('input[type=checkbox]').addEventListener('change', e => meal.classList.toggle('is-off', !e.target.checked));
            });
            document.querySelectorAll('[data-rd-buyout]').forEach(radio => radio.addEventListener('change', () => {
                document.getElementById('rdBuyoutFields').hidden = radio.dataset.rdBuyout !== '1';
            }));
        </script>
    @endpush
</x-band-manager.layout>
