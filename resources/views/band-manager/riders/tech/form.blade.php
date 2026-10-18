@php
    $listOptions = compact('canEdit', 'stands', 'monitorTypes', 'providers', 'textMax');
    $rows = fn (string $list, ?array $fallback = null): array => array_values((array) old($list, $rider->exists ? ($rider->{$list} ?? []) : ($fallback ?? [])));
    $presets = [
        'line_up' => $suggestedLineUp,
        'inputs' => $templates['inputs'] ?? [],
        'monitors' => $templates['monitors'] ?? [],
        'backline' => $templates['backline'] ?? [],
    ];
    $sections = [
        'contact' => ['person-badge', 'Kontakt'],
        'line-up' => ['people', 'Besetzung'],
        'times' => ['clock', 'Zeiten'],
        'inputs' => ['list-ol', 'Kanalliste'],
        'monitors' => ['speaker', 'Monitoring'],
        'backline' => ['boombox', 'Backline'],
        'stage' => ['easel', 'Bühne'],
        'pa' => ['soundwave', 'PA & FOH'],
        'light' => ['lightbulb', 'Licht'],
        'power' => ['plug', 'Strom'],
        'custom' => ['plus-square', 'Eigene Abschnitte'],
        'notes' => ['chat-left-text', 'Sonstiges'],
    ];
    $minutes = [
        'setup_minutes' => 'Aufbau',
        'soundcheck_minutes' => 'Soundcheck',
        'show_minutes' => 'Showlänge',
        'teardown_minutes' => 'Abbau',
    ];
@endphp
<x-band-manager.layout :title="'Tech Rider – '.$band->name">
    @push('styles')
        <link href="{{ asset('assets/band-manager/css/riders.css') }}?v=2" rel="stylesheet">
    @endpush

    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <div>
            @if ($bands->count() > 1)<a href="{{ route('band-manager.tech-riders.index') }}" class="small">&larr; Alle Bands</a>@endif
            <h1 class="h3 mb-0">{{ $band->name }} – Tech Rider</h1>
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
                <x-band-manager.band-switcher :bands="$bands" :current="$band" route="band-manager.tech-riders.band" />
            @endif
            @if ($rider->exists)
                <a href="{{ route('band-manager.tech-riders.pdf', $band) }}" class="btn btn-primary" data-rd-pdf><i class="bi bi-download"></i> Fertigen Rider herunterladen (PDF)</a>
            @endif
        </div>
    </div>

    @if ($canEdit && ! $rider->exists)
        <div class="alert alert-info hv-flash">
            <i class="bi bi-lightbulb"></i>
            <span>
                <strong>So geht's:</strong> Füllt aus, was ihr für eure Show braucht – alles andere einfach leer lassen.
                Mit <strong>„Vorlage einfügen“</strong> bekommt ihr eine typische Band-Besetzung als Startpunkt, die ihr dann anpasst.
                Über <strong>„+ Zeile“</strong> und <strong>„Eigene Abschnitte“</strong> ergänzt ihr alles, was hier noch fehlt.
                Nach dem Speichern könnt ihr den fertigen Rider als <strong>PDF herunterladen</strong> – mit eurem Bandlogo aus dem Bandprofil.
            </span>
        </div>
    @endif

    <nav class="rd-jump" aria-label="Abschnitte">
        @foreach ($sections as $anchor => [$icon, $label])
            <a href="#rd-{{ $anchor }}"><i class="bi bi-{{ $icon }}"></i> {{ $label }}</a>
        @endforeach
    </nav>

    <form method="post" action="{{ route('band-manager.tech-riders.update', $band) }}" enctype="multipart/form-data" id="rdForm" class="rd-form">
        @csrf
        @method('PUT')

        <fieldset @disabled(! $canEdit)>

            {{-- ================= Kontakt ================= --}}
            <section class="card card-body shadow-sm rd-card" id="rd-contact">
                <h2 class="rd-title"><i class="bi bi-person-badge"></i> Technischer Ansprechpartner</h2>
                <p class="rd-help">Wen kann die Technik vor Ort bei Fragen erreichen?</p>
                <div class="row g-3">
                    <x-band-manager.field name="contact_name" label="Name" :value="$rider->contact_name" col="col-md-4" />
                    <x-band-manager.field name="contact_phone" label="Telefon" :value="$rider->contact_phone" col="col-md-4" type="tel" :max="50" />
                    <x-band-manager.field name="contact_email" label="E-Mail" :value="$rider->contact_email" col="col-md-4" type="email" :max="190" />
                    <div class="col-12 d-flex flex-wrap gap-4">
                        @foreach (['brings_foh_engineer' => 'Wir bringen eigenen FOH-Techniker mit', 'brings_monitor_engineer' => 'Wir bringen eigenen Monitor-Techniker mit'] as $field => $label)
                            <div class="form-check m-0">
                                <input type="hidden" name="{{ $field }}" value="0">
                                <input class="form-check-input" type="checkbox" name="{{ $field }}" value="1" id="{{ $field }}" @checked(old($field, $rider->{$field}))>
                                <label class="form-check-label" for="{{ $field }}">{{ $label }}</label>
                            </div>
                        @endforeach
                    </div>
                </div>
            </section>

            {{-- ================= Besetzung ================= --}}
            <section class="card card-body shadow-sm rd-card" id="rd-line-up">
                <h2 class="rd-title"><i class="bi bi-people"></i> Besetzung</h2>
                <p class="rd-help">Wer steht auf der Bühne und spielt was?</p>
                @include('band-manager.riders.tech.list', $listOptions + [
                    'list' => 'line_up',
                    'rows' => $rows('line_up', $suggestedLineUp),
                    'addLabel' => 'Person',
                    'empty' => 'Noch niemand eingetragen.',
                    'presetLabel' => $suggestedLineUp !== [] ? 'Aus „Band/Team“ übernehmen' : null,
                ])
            </section>

            {{-- ================= Zeiten ================= --}}
            <section class="card card-body shadow-sm rd-card" id="rd-times">
                <h2 class="rd-title"><i class="bi bi-clock"></i> Zeiten</h2>
                <p class="rd-help">Wie viel Zeit braucht ihr? Die genauen Uhrzeiten stehen im Day Sheet des jeweiligen Gigs.</p>
                <div class="row g-3">
                    @foreach ($minutes as $field => $label)
                        <div class="col-6 col-md-3">
                            <label class="form-label" for="{{ $field }}">{{ $label }}</label>
                            <div class="input-group">
                                <input type="number" name="{{ $field }}" id="{{ $field }}" class="form-control" min="0" max="1440" step="5" value="{{ old($field, $rider->{$field}) }}">
                                <span class="input-group-text">Min.</span>
                            </div>
                        </div>
                    @endforeach
                </div>
            </section>

            {{-- ================= Kanalliste ================= --}}
            <section class="card card-body shadow-sm rd-card" id="rd-inputs">
                <h2 class="rd-title"><i class="bi bi-list-ol"></i> Kanalliste (Input List)</h2>
                <p class="rd-help">Jede Quelle, die ans Mischpult geht – in der Reihenfolge der Kanäle. Mit den Pfeilen sortiert ihr um, die Nummern passen sich an.</p>
                @include('band-manager.riders.tech.list', $listOptions + [
                    'list' => 'inputs',
                    'rows' => $rows('inputs'),
                    'addLabel' => 'Kanal',
                    'empty' => 'Noch keine Kanäle – fügt einen Kanal hinzu oder nutzt die Vorlage.',
                    'presetLabel' => 'Vorlage einfügen (Rockband)',
                ])
            </section>

            {{-- ================= Monitoring ================= --}}
            <section class="card card-body shadow-sm rd-card" id="rd-monitors">
                <h2 class="rd-title"><i class="bi bi-speaker"></i> Monitoring</h2>
                <p class="rd-help">Ein Eintrag pro Monitorweg (Mix): Wedge, In-Ear oder Drumfill – und was ihr darauf hören wollt.</p>
                @include('band-manager.riders.tech.list', $listOptions + [
                    'list' => 'monitors',
                    'rows' => $rows('monitors'),
                    'addLabel' => 'Monitorweg',
                    'empty' => 'Noch keine Monitorwege.',
                    'presetLabel' => 'Vorlage einfügen',
                ])
                <div class="row g-3 mt-1">
                    <x-band-manager.textarea name="monitor_notes" label="Weitere Hinweise zum Monitoring" :value="$rider->monitor_notes" :max="$textMax" rows="3"
                                             placeholder="z. B. eigenes In-Ear-Rack, benötigte Splits, Talkback …" />
                </div>
            </section>

            {{-- ================= Backline ================= --}}
            <section class="card card-body shadow-sm rd-card" id="rd-backline">
                <h2 class="rd-title"><i class="bi bi-boombox"></i> Backline</h2>
                <p class="rd-help">Verstärker, Drums, Ständer … – was bringt ihr selbst mit, was soll der Veranstalter stellen?</p>
                @include('band-manager.riders.tech.list', $listOptions + [
                    'list' => 'backline',
                    'rows' => $rows('backline'),
                    'addLabel' => 'Gerät',
                    'empty' => 'Noch keine Backline eingetragen.',
                    'presetLabel' => 'Vorlage einfügen',
                ])
            </section>

            {{-- ================= Bühne ================= --}}
            <section class="card card-body shadow-sm rd-card" id="rd-stage">
                <h2 class="rd-title"><i class="bi bi-easel"></i> Bühne &amp; Bühnenplan</h2>
                <p class="rd-help">Mindestgröße der Bühne, Riser und euer Bühnenplan (Stage Plot) als Bild.</p>
                <div class="row g-3">
                    @foreach (['stage_width' => 'Bühnenbreite mind.', 'stage_depth' => 'Bühnentiefe mind.'] as $field => $label)
                        <div class="col-6 col-md-3">
                            <label class="form-label" for="{{ $field }}">{{ $label }}</label>
                            <div class="input-group">
                                <input type="number" name="{{ $field }}" id="{{ $field }}" class="form-control" min="0" max="999" step="0.5" value="{{ old($field, $rider->{$field}) }}">
                                <span class="input-group-text">m</span>
                            </div>
                        </div>
                    @endforeach
                    <x-band-manager.textarea name="stage_notes" label="Hinweise zur Bühne" :value="$rider->stage_notes" :max="$textMax" rows="3"
                                             placeholder="z. B. Drum-Riser 2 x 3 m, 40 cm hoch · Platz für Keyboard-Riser · Bühne muss überdacht sein …" />

                    <div class="col-md-5">
                        <div class="rd-plot" id="rdPlotPreview">
                            @if ($hasStagePlot)
                                <a href="{{ route('band-manager.tech-riders.stage-plot', $band) }}" target="_blank" rel="noopener">
                                    <img src="{{ route('band-manager.tech-riders.stage-plot', $band) }}" alt="Bühnenplan {{ $band->name }}">
                                </a>
                            @else
                                <span class="rd-plot-empty"><i class="bi bi-easel"></i><br>Noch kein Bühnenplan</span>
                            @endif
                        </div>
                    </div>
                    <div class="col-md-7">
                        <label class="form-label" for="rdPlotInput">Bühnenplan hochladen</label>
                        <input type="file" name="stage_plot" id="rdPlotInput" class="form-control" accept="image/png,image/jpeg">
                        <div class="form-text">
                            PNG oder JPG · max. {{ $stagePlotMaxMb }} MB. Zeichnet ein, wer wo steht, wo Monitore, Amps und Strom gebraucht werden.
                            Eine Skizze vom Handy-Foto reicht völlig.
                        </div>
                        @if ($hasStagePlot)
                            <div class="d-flex flex-wrap align-items-center gap-3 mt-2">
                                <a href="{{ route('band-manager.tech-riders.stage-plot', ['band' => $band, 'download' => 1]) }}" class="small"><i class="bi bi-download"></i> Bühnenplan herunterladen</a>
                                <div class="form-check m-0">
                                    <input class="form-check-input" type="checkbox" name="remove_stage_plot" value="1" id="remove_stage_plot">
                                    <label class="form-check-label small" for="remove_stage_plot">Bühnenplan entfernen</label>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            </section>

            {{-- ================= PA, Licht, Strom ================= --}}
            <section class="card card-body shadow-sm rd-card" id="rd-pa">
                <h2 class="rd-title"><i class="bi bi-soundwave"></i> PA &amp; FOH</h2>
                <p class="rd-help">Anlage und Mischpult – was ist euch wichtig?</p>
                <div class="row g-3">
                    <x-band-manager.textarea name="pa_notes" label="Anforderungen an PA und Mischpult" :value="$rider->pa_notes" :max="$textMax" rows="4"
                                             placeholder="z. B. professionelle PA passend zur Raumgröße, mind. 24 Kanäle, digitales Pult (X32, SQ5 o. ä.), 4 Monitorwege, Hall + Delay …" />
                </div>
            </section>

            <section class="card card-body shadow-sm rd-card" id="rd-light">
                <h2 class="rd-title"><i class="bi bi-lightbulb"></i> Licht</h2>
                <div class="row g-3">
                    <x-band-manager.textarea name="lighting_notes" label="Wünsche zum Licht" :value="$rider->lighting_notes" :max="$textMax" rows="3"
                                             placeholder="z. B. eigener Lichttechniker, Farbwünsche, kein Stroboskop, Hazer erwünscht …" />
                </div>
            </section>

            <section class="card card-body shadow-sm rd-card" id="rd-power">
                <h2 class="rd-title"><i class="bi bi-plug"></i> Strom</h2>
                <div class="row g-3">
                    <x-band-manager.textarea name="power_notes" label="Stromanschlüsse" :value="$rider->power_notes" :max="$textMax" rows="3"
                                             placeholder="z. B. 230 V Schuko an jeder Position (Drums, Bass, Gitarre links/rechts, Keys) · 1 x CEE 16 A für Licht …" />
                </div>
            </section>

            {{-- ================= Eigene Abschnitte ================= --}}
            <section class="card card-body shadow-sm rd-card" id="rd-custom">
                <h2 class="rd-title"><i class="bi bi-plus-square"></i> Eigene Abschnitte</h2>
                <p class="rd-help">Alles, was oben keinen Platz hat – legt dafür einfach einen eigenen Abschnitt mit Titel an.</p>
                @include('band-manager.riders.tech.list', $listOptions + [
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
                                             placeholder="z. B. Abweichungen nur nach Absprache, Bitte um Rückmeldung bis 14 Tage vor der Show …" />
                </div>
            </section>

        </fieldset>

        @if ($canEdit)
            <div class="rd-savebar">
                <span class="rd-dirty small" id="rdDirty" hidden><i class="bi bi-circle-fill"></i> Ungespeicherte Änderungen</span>
                @if ($rider->exists)
                    <a href="{{ route('band-manager.tech-riders.pdf', $band) }}" class="btn btn-outline-secondary" data-rd-pdf><i class="bi bi-download"></i> PDF</a>
                @endif
                <button class="btn btn-primary"><i class="bi bi-check-lg"></i> Speichern</button>
            </div>
        @endif
    </form>

    @push('scripts')
        <script>window.RD_PRESETS = @json($presets);</script>
        <script src="{{ asset('assets/band-manager/js/tech-rider.js') }}?v=2"></script>
    @endpush
</x-band-manager.layout>
