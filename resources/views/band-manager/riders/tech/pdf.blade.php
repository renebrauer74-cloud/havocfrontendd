{{-- Tech Rider – Vorlage für Dompdf (nur Tabellen + einfaches CSS, kein Flexbox) --}}
@php
    use App\Enums\BandManager\BacklineProviderEnum;
    use App\Enums\BandManager\MicStandEnum;
    use App\Enums\BandManager\MonitorTypeEnum;

    $lineUp = $rider->line_up ?? [];
    $inputs = $rider->inputs ?? [];
    $monitors = $rider->monitors ?? [];
    $customSections = $rider->custom_sections ?? [];

    // Backline: zuerst, was der Veranstalter stellen soll – das ist für ihn das Wichtigste
    $backline = collect($rider->backline ?? [])->groupBy(fn (array $item): string => (string) ($item['provider'] ?? ''));
    $backlineGroups = array_filter([
        BacklineProviderEnum::Venue->value => BacklineProviderEnum::Venue->label(),
        BacklineProviderEnum::Band->value => BacklineProviderEnum::Band->label(),
        '' => 'Ohne Zuordnung',
    ], fn (string $label, string $key): bool => $backline->has($key), ARRAY_FILTER_USE_BOTH);

    $times = array_filter([
        'Aufbau' => $rider->setup_minutes,
        'Soundcheck' => $rider->soundcheck_minutes,
        'Showlänge' => $rider->show_minutes,
        'Abbau' => $rider->teardown_minutes,
    ], fn (?int $minutes): bool => $minutes !== null);

    $meters = fn (mixed $value): string => $value !== null ? str_replace('.', ',', (string) (float) $value).' m' : '–';
    $stageSize = $rider->stage_width !== null || $rider->stage_depth !== null
        ? $meters($rider->stage_width).' breit × '.$meters($rider->stage_depth).' tief'
        : '';

    $contact = array_filter([
        'Name' => e((string) $rider->contact_name),
        'Telefon' => filled($rider->contact_phone) ? '<a href="tel:'.e(preg_replace('/[^0-9+]/', '', $rider->contact_phone)).'">'.e($rider->contact_phone).'</a>' : '',
        'E-Mail' => filled($rider->contact_email) ? '<a href="mailto:'.e($rider->contact_email).'">'.e($rider->contact_email).'</a>' : '',
        'FOH-Technik' => $rider->brings_foh_engineer ? 'bringt die Band mit' : '',
        'Monitor-Technik' => $rider->brings_monitor_engineer ? 'bringt die Band mit' : '',
    ], fn (string $html): bool => $html !== '');

    $textSections = array_filter([
        'PA & FOH' => $rider->pa_notes,
        'Licht' => $rider->lighting_notes,
        'Strom' => $rider->power_notes,
    ], fn (?string $text): bool => filled($text));

    // Nummerierte Abschnitts-Überschrift: 01, 02 …
    $number = 0;
    $heading = function (string $title, string $subtitle = '') use (&$number): string {
        $number++;

        return '<table class="head"><tr><td class="no">'.sprintf('%02d', $number).'</td><td class="ttl">'.e($title)
            .($subtitle !== '' ? ' <span class="cnt">'.e($subtitle).'</span>' : '').'</td></tr></table>';
    };

    // Logo höchstens 230 × 80 pt, Seitenverhältnis bleibt
    $logoScale = $bandLogo ? min(230 / $bandLogo['w'], 80 / $bandLogo['h'], 1) : 1;
@endphp
<!DOCTYPE html>
<html lang="de">
<head>
<meta charset="UTF-8">
<style>
    @page { margin: 30px 36px 48px 36px; }
    body  { font-family: "DejaVu Sans", sans-serif; font-size: 9px; color: #1b1b20; line-height: 1.4; }
    a     { color: #1b1b20; text-decoration: none; }
    table { width: 100%; border-collapse: collapse; }

    strong { font-weight: bold; }
    em     { font-style: italic; }
    u      { text-decoration: underline; }
    s      { text-decoration: line-through; }

    /* Kopf: Bandlogo in der Mitte */
    .cover       { text-align: center; padding-bottom: 10px; }
    .cover .band { font-size: 26px; font-weight: bold; letter-spacing: 2px; color: #0b0b0e; }
    .cover .doc  { margin-top: 8px; font-size: 10px; font-weight: bold; letter-spacing: 6px; color: #0b0b0e; }
    .cover .meta { margin-top: 2px; font-size: 8px; color: #6b6b77; }
    .stripe      { height: 4px; background: #0b0b0e; border-bottom: 2px solid #dc2f32; margin-bottom: 12px; }

    /* Eckdaten */
    .facts    { border-collapse: separate; border-spacing: 5px 0; margin-left: -5px; width: 101.8%; }
    .facts td { width: 25%; border: 1px solid #1b1b20; padding: 6px 8px; text-align: center; }
    .facts .v { font-size: 15px; font-weight: bold; color: #0b0b0e; }
    .facts .l { font-size: 6.5px; letter-spacing: 1.5px; text-transform: uppercase; color: #6b6b77; }

    /* Abschnitts-Überschrift */
    .head        { margin: 16px 0 6px; }
    .head td     { background: #0b0b0e; color: #ffffff; padding: 5px 8px; }
    .head td.no  { width: 22px; background: #dc2f32; font-weight: bold; text-align: center; }
    .head td.ttl { font-weight: bold; letter-spacing: 2px; text-transform: uppercase; font-size: 8.5px; }
    .head .cnt   { font-weight: normal; letter-spacing: 0; text-transform: none; color: #b9b9c3; }
    .block       { page-break-inside: avoid; }

    /* Tabellen mit Rahmen */
    .grid th { background: #2a2a31; color: #ffffff; text-align: left; font-size: 7px; letter-spacing: 1px;
               text-transform: uppercase; padding: 5px 6px; border: 1px solid #2a2a31; font-weight: bold; }
    .grid td { border: 1px solid #9d9da8; padding: 5px 6px; vertical-align: top; }
    .grid tr.alt td { background: #f3f3f6; }
    .grid td.ch  { width: 26px; text-align: center; font-weight: bold; font-size: 10px; background: #e7e7ec; }
    .grid td.c   { text-align: center; }
    .grid td.k   { width: 30%; background: #f3f3f6; font-weight: bold; }
    .grid tr.group td { background: #e7e7ec; font-weight: bold; text-transform: uppercase; letter-spacing: 1px; font-size: 7.5px; color: #0b0b0e; }
    .p48  { font-weight: bold; color: #dc2f32; }
    .muted { color: #6b6b77; }

    .text { border: 1px solid #9d9da8; padding: 7px 9px; }
    .plot { border: 1px solid #9d9da8; padding: 8px; text-align: center; margin-top: 6px; }
    .split td.half { width: 50%; vertical-align: top; }
</style>
</head>
<body>

{{-- ================= Kopf ================= --}}
<div class="cover">
    @if ($bandLogo)
        <img src="{{ $bandLogo['src'] }}" width="{{ (int) round($bandLogo['w'] * $logoScale) }}" height="{{ (int) round($bandLogo['h'] * $logoScale) }}" alt="{{ $band->name }}">
    @else
        <div class="band">{{ $band->name }}</div>
    @endif
    <div class="doc">TECHNICAL RIDER</div>
    <div class="meta">{{ $band->name }}@if ($band->genre) · {{ $band->genre }}@endif · Stand {{ $rider->updated_at?->format('d.m.Y') }}</div>
</div>
<div class="stripe"></div>

<table class="facts">
    <tr>
        <td><div class="v">{{ count($inputs) ?: '–' }}</div><div class="l">Kanäle</div></td>
        <td><div class="v">{{ count($monitors) ?: '–' }}</div><div class="l">Monitorwege</div></td>
        <td><div class="v">{{ $rider->soundcheck_minutes !== null ? $rider->soundcheck_minutes.' Min.' : '–' }}</div><div class="l">Soundcheck</div></td>
        <td><div class="v">{{ $rider->show_minutes !== null ? $rider->show_minutes.' Min.' : '–' }}</div><div class="l">Showlänge</div></td>
    </tr>
</table>

{{-- ================= Kontakt & Zeiten ================= --}}
@if ($contact || $times)
<div class="block">
    {!! $heading('Kontakt & Zeitbedarf') !!}
    <table class="split">
        <tr>
            <td class="half" style="padding-right:4px;">
                <table class="grid">
                    <tr><th colspan="2">Technischer Ansprechpartner</th></tr>
                    @forelse ($contact as $label => $html)
                        <tr><td class="k">{{ $label }}</td><td>{!! $html !!}</td></tr>
                    @empty
                        <tr><td colspan="2" class="muted">–</td></tr>
                    @endforelse
                </table>
            </td>
            <td class="half" style="padding-left:4px;">
                <table class="grid">
                    <tr><th colspan="2">Zeitbedarf</th></tr>
                    @forelse ($times as $label => $minutes)
                        <tr><td class="k">{{ $label }}</td><td>{{ $minutes }} Min.</td></tr>
                    @empty
                        <tr><td colspan="2" class="muted">–</td></tr>
                    @endforelse
                </table>
            </td>
        </tr>
    </table>
</div>
@endif

{{-- ================= Besetzung ================= --}}
@if ($lineUp)
<div class="block">
    {!! $heading('Besetzung', count($lineUp).' Personen') !!}
    <table class="grid">
        <thead><tr><th style="width:26px">Nr.</th><th style="width:45%">Name</th><th>Instrument / Funktion</th></tr></thead>
        @foreach ($lineUp as $i => $person)
            <tr @class(['alt' => $i % 2 === 1])>
                <td class="ch">{{ $i + 1 }}</td>
                <td><strong>{{ $person['name'] ?? '' }}</strong></td>
                <td>{{ $person['instrument'] ?? '' }}</td>
            </tr>
        @endforeach
    </table>
</div>
@endif

{{-- ================= Kanalliste ================= --}}
@if ($inputs)
    {!! $heading('Kanalliste / Input List', count($inputs).' Kanäle') !!}
    <table class="grid">
        <thead>
            <tr>
                <th style="width:26px">Ch</th>
                <th style="width:24%">Instrument / Quelle</th>
                <th style="width:22%">Mikrofon / DI</th>
                <th style="width:15%">Stativ</th>
                <th style="width:34px">+48 V</th>
                <th>Bemerkung</th>
            </tr>
        </thead>
        @foreach ($inputs as $i => $input)
            <tr @class(['alt' => $i % 2 === 1])>
                <td class="ch">{{ $i + 1 }}</td>
                <td><strong>{{ $input['source'] ?? '' }}</strong></td>
                <td>{{ $input['mic'] ?? '' }}</td>
                <td>{{ MicStandEnum::tryFrom((string) ($input['stand'] ?? ''))?->label() }}</td>
                <td class="c">@if (! empty($input['phantom']))<span class="p48">✓</span>@endif</td>
                <td>{{ $input['note'] ?? '' }}</td>
            </tr>
        @endforeach
    </table>
@endif

{{-- ================= Monitoring ================= --}}
@if ($monitors || filled($rider->monitor_notes))
<div class="block">
    {!! $heading('Monitoring', $monitors ? count($monitors).' Wege' : '') !!}
    @if ($monitors)
        <table class="grid">
            <thead><tr><th style="width:26px">Weg</th><th style="width:24%">Für wen</th><th style="width:22%">Art</th><th>Mix-Wunsch</th></tr></thead>
            @foreach ($monitors as $i => $monitor)
                <tr @class(['alt' => $i % 2 === 1])>
                    <td class="ch">{{ $i + 1 }}</td>
                    <td><strong>{{ $monitor['who'] ?? '' }}</strong></td>
                    <td>{{ MonitorTypeEnum::tryFrom((string) ($monitor['type'] ?? ''))?->label() }}</td>
                    <td>{{ $monitor['note'] ?? '' }}</td>
                </tr>
            @endforeach
        </table>
    @endif
    @if (filled($rider->monitor_notes))
        <div class="text" @if ($monitors) style="margin-top:6px" @endif>{!! md($rider->monitor_notes) !!}</div>
    @endif
</div>
@endif

{{-- ================= Backline ================= --}}
@if ($backlineGroups)
<div class="block">
    {!! $heading('Backline', $backline->flatten(1)->count().' Positionen') !!}
    <table class="grid">
        <thead><tr><th style="width:26px">Nr.</th><th style="width:48%">Gerät</th><th>Bemerkung</th></tr></thead>
        @foreach ($backlineGroups as $key => $label)
            <tr class="group"><td colspan="3">{{ $label }}</td></tr>
            @foreach ($backline[$key] as $i => $item)
                <tr @class(['alt' => $i % 2 === 1])>
                    <td class="ch">{{ $i + 1 }}</td>
                    <td><strong>{{ $item['item'] ?? '' }}</strong></td>
                    <td>{{ $item['note'] ?? '' }}</td>
                </tr>
            @endforeach
        @endforeach
    </table>
</div>
@endif

{{-- ================= Bühne ================= --}}
@if ($stageSize !== '' || filled($rider->stage_notes))
<div class="block">
    {!! $heading('Bühne') !!}
    <table class="grid">
        @if ($stageSize !== '')<tr><td class="k">Mindestgröße</td><td><strong>{{ $stageSize }}</strong></td></tr>@endif
        @if (filled($rider->stage_notes))<tr><td class="k">Hinweise</td><td>{!! md($rider->stage_notes) !!}</td></tr>@endif
    </table>
</div>
@endif

@if ($stagePlot)
<div class="block">
    {!! $heading('Bühnenplan / Stage Plot') !!}
    @php($plotScale = min(510 / $stagePlot['w'], 500 / $stagePlot['h'], 1))
    <div class="plot"><img src="{{ $stagePlot['src'] }}" width="{{ (int) round($stagePlot['w'] * $plotScale) }}" height="{{ (int) round($stagePlot['h'] * $plotScale) }}" alt="Bühnenplan"></div>
</div>
@endif

{{-- ================= Freitexte & eigene Abschnitte ================= --}}
@foreach ($textSections as $title => $text)
    <div class="block">
        {!! $heading($title) !!}
        <div class="text">{!! md($text) !!}</div>
    </div>
@endforeach

@foreach ($customSections as $customSection)
    <div class="block">
        {!! $heading(($customSection['title'] ?? '') ?: 'Weitere Anforderungen') !!}
        <div class="text">{!! md($customSection['content'] ?? '') !!}</div>
    </div>
@endforeach

@if (filled($rider->notes))
<div class="block">
    {!! $heading('Sonstiges') !!}
    <div class="text">{!! md($rider->notes) !!}</div>
</div>
@endif

</body>
</html>
