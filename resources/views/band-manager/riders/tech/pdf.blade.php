{{-- Tech Rider – Vorlage für Dompdf (nur Tabellen + einfaches CSS, kein Flexbox) --}}
@php
    use App\Enums\BandManager\MicStandEnum;
    use App\Enums\BandManager\MonitorTypeEnum;

    $lineUp = $rider->line_up ?? [];
    $inputs = $rider->inputs ?? [];
    $monitors = $rider->monitors ?? [];
    $backline = collect($rider->backline ?? []);
    $customSections = $rider->custom_sections ?? [];

    $times = array_filter([
        'Aufbau' => $rider->setup_minutes,
        'Soundcheck' => $rider->soundcheck_minutes,
        'Showlänge' => $rider->show_minutes,
        'Abbau' => $rider->teardown_minutes,
    ], fn (?int $minutes): bool => $minutes !== null);

    $stageSize = collect([$rider->stage_width, $rider->stage_depth])->filter(fn ($value): bool => $value !== null)->isNotEmpty()
        ? ($rider->stage_width !== null ? str_replace('.', ',', (string) (float) $rider->stage_width).' m' : '–')
            .' × '.($rider->stage_depth !== null ? str_replace('.', ',', (string) (float) $rider->stage_depth).' m' : '–').' (B × T)'
        : '';

    $engineers = array_filter([
        $rider->brings_foh_engineer ? 'eigener FOH-Techniker' : null,
        $rider->brings_monitor_engineer ? 'eigener Monitor-Techniker' : null,
    ]);

    $notes = [
        'PA & FOH' => $rider->pa_notes,
        'Licht' => $rider->lighting_notes,
        'Strom' => $rider->power_notes,
    ];
@endphp
<!DOCTYPE html>
<html lang="de">
<head>
<meta charset="UTF-8">
<style>
    @page { margin: 32px 40px 50px 40px; }
    body  { font-family: "DejaVu Sans", sans-serif; font-size: 9.5px; color: #1e1e24; line-height: 1.45; }
    a     { color: #1e1e24; text-decoration: none; }
    table { width: 100%; border-collapse: collapse; }

    strong { font-weight: bold; }
    em     { font-style: italic; }
    u      { text-decoration: underline; }
    s      { text-decoration: line-through; }

    .top td     { vertical-align: middle; }
    .top img    { height: 44px; }
    .brand      { font-size: 24px; font-weight: bold; letter-spacing: 6px; }
    .doc        { text-align: right; }
    .doc .lbl   { font-size: 8px; letter-spacing: 4px; color: #dc2f32; font-weight: bold; }
    .doc .date  { font-size: 12px; font-weight: bold; color: #0b0b0e; }
    .rule       { border-top: 3px solid #0b0b0e; margin: 10px 0 14px; }

    .title    { font-size: 20px; font-weight: bold; color: #0b0b0e; }
    .subtitle { font-size: 11px; color: #6b6b77; margin-top: 2px; }

    .facts       { margin-top: 12px; border-collapse: separate; border-spacing: 4px 0; margin-left: -4px; }
    .facts td    { background: #f4f4f6; padding: 7px 9px; vertical-align: top; }
    .lbl-sm      { font-size: 7px; letter-spacing: 1.5px; text-transform: uppercase; color: #6b6b77; }
    .facts .val  { font-size: 11px; font-weight: bold; color: #0b0b0e; }

    h2 { font-size: 9px; letter-spacing: 2px; text-transform: uppercase; color: #dc2f32;
         border-bottom: 1px solid #e2e2e8; padding-bottom: 3px; margin: 18px 0 6px; }
    .section { page-break-inside: avoid; }

    .kv td   { padding: 3px 0; vertical-align: top; }
    .kv td.k { width: 28%; color: #6b6b77; }

    .list th { text-align: left; font-size: 7px; letter-spacing: 1px; text-transform: uppercase; color: #6b6b77;
               padding: 3px 6px; border-bottom: 1px solid #cdcdd5; font-weight: normal; }
    .list td { padding: 4px 6px; border-bottom: 1px solid #ececf0; vertical-align: top; }
    .list td.no { width: 22px; font-weight: bold; color: #0b0b0e; }
    .list tr.even td { background: #f8f8fa; }
    .p48 { background: #dc2f32; color: #fff; padding: 0 4px; font-weight: bold; font-size: 7.5px; }
    .sub { font-size: 8px; font-weight: bold; color: #0b0b0e; margin: 8px 0 3px; }
    .muted { color: #6b6b77; }
    .plot  { text-align: center; margin-top: 8px; }
</style>
</head>
<body>

<table class="top">
    <tr>
        <td>@if ($logo)<img src="{{ $logo }}" alt="Havoc">@else<span class="brand">HAVOC</span>@endif</td>
        <td class="doc">
            <div class="lbl">TECHNICAL RIDER</div>
            <div class="date">Stand {{ $rider->updated_at?->format('d.m.Y') }}</div>
        </td>
    </tr>
</table>
<div class="rule"></div>

<div class="title">{{ $band->name }}</div>
<div class="subtitle">Technical Rider @if ($band->genre)· {{ $band->genre }}@endif</div>

@if ($times || $inputs || $monitors)
<table class="facts">
    <tr>
        <td><div class="lbl-sm">Kanäle</div><div class="val">{{ count($inputs) ?: '–' }}</div></td>
        <td><div class="lbl-sm">Monitorwege</div><div class="val">{{ count($monitors) ?: '–' }}</div></td>
        <td><div class="lbl-sm">Soundcheck</div><div class="val">{{ $rider->soundcheck_minutes !== null ? $rider->soundcheck_minutes.' Min.' : '–' }}</div></td>
        <td><div class="lbl-sm">Showlänge</div><div class="val">{{ $rider->show_minutes !== null ? $rider->show_minutes.' Min.' : '–' }}</div></td>
    </tr>
</table>
@endif

@if (filled($rider->contact_name) || filled($rider->contact_phone) || filled($rider->contact_email) || $engineers)
<div class="section">
    <h2>Technischer Ansprechpartner</h2>
    <table class="kv">
        @if (filled($rider->contact_name))<tr><td class="k">Name</td><td><strong>{{ $rider->contact_name }}</strong></td></tr>@endif
        @if (filled($rider->contact_phone))<tr><td class="k">Telefon</td><td><a href="tel:{{ preg_replace('/[^0-9+]/', '', $rider->contact_phone) }}">{{ $rider->contact_phone }}</a></td></tr>@endif
        @if (filled($rider->contact_email))<tr><td class="k">E-Mail</td><td><a href="mailto:{{ $rider->contact_email }}">{{ $rider->contact_email }}</a></td></tr>@endif
        @if ($engineers)<tr><td class="k">Wir bringen mit</td><td>{{ implode(', ', $engineers) }}</td></tr>@endif
    </table>
</div>
@endif

@if ($lineUp)
<div class="section">
    <h2>Besetzung ({{ count($lineUp) }} Personen)</h2>
    <table class="list">
        <tr><th>Name</th><th>Instrument / Funktion</th></tr>
        @foreach ($lineUp as $person)
            <tr><td><strong>{{ $person['name'] ?? '' }}</strong></td><td>{{ $person['instrument'] ?? '' }}</td></tr>
        @endforeach
    </table>
</div>
@endif

@if ($times)
<div class="section">
    <h2>Zeitbedarf</h2>
    <table class="kv">
        @foreach ($times as $label => $minutes)
            <tr><td class="k">{{ $label }}</td><td>{{ $minutes }} Min.</td></tr>
        @endforeach
    </table>
</div>
@endif

@if ($inputs)
<div>
    <h2>Kanalliste (Input List)</h2>
    <table class="list">
        <tr><th>Nr.</th><th>Instrument / Quelle</th><th>Mikrofon / DI</th><th>Stativ</th><th>+48 V</th><th>Bemerkung</th></tr>
        @foreach ($inputs as $i => $input)
            <tr @class(['even' => $i % 2 === 1])>
                <td class="no">{{ $i + 1 }}</td>
                <td><strong>{{ $input['source'] ?? '' }}</strong></td>
                <td>{{ $input['mic'] ?? '' }}</td>
                <td>{{ MicStandEnum::tryFrom((string) ($input['stand'] ?? ''))?->label() }}</td>
                <td>@if (! empty($input['phantom']))<span class="p48">48V</span>@endif</td>
                <td>{{ $input['note'] ?? '' }}</td>
            </tr>
        @endforeach
    </table>
</div>
@endif

@if ($monitors || filled($rider->monitor_notes))
<div class="section">
    <h2>Monitoring</h2>
    @if ($monitors)
        <table class="list">
            <tr><th>Weg</th><th>Für wen</th><th>Art</th><th>Mix-Wunsch</th></tr>
            @foreach ($monitors as $i => $monitor)
                <tr>
                    <td class="no">{{ $i + 1 }}</td>
                    <td><strong>{{ $monitor['who'] ?? '' }}</strong></td>
                    <td>{{ MonitorTypeEnum::tryFrom((string) ($monitor['type'] ?? ''))?->label() }}</td>
                    <td>{{ $monitor['note'] ?? '' }}</td>
                </tr>
            @endforeach
        </table>
    @endif
    @if (filled($rider->monitor_notes))<p>{!! md($rider->monitor_notes) !!}</p>@endif
</div>
@endif

@if ($backline->isNotEmpty())
<div class="section">
    <h2>Backline</h2>
    @php($groups = $backline->groupBy(fn (array $item): string => (string) ($item['provider'] ?? '')))
    @foreach ([...array_map(fn ($provider) => [$provider->value, $provider->label()], $providers), ['', 'Weitere']] as [$key, $label])
        @if ($groups->has($key))
            <div class="sub">{{ $label }}</div>
            <table class="list">
                @foreach ($groups[$key] as $item)
                    <tr><td style="width:60%"><strong>{{ $item['item'] ?? '' }}</strong></td><td class="muted">{{ $item['note'] ?? '' }}</td></tr>
                @endforeach
            </table>
        @endif
    @endforeach
</div>
@endif

@if ($stageSize !== '' || filled($rider->stage_notes) || $stagePlot)
<div class="section">
    <h2>Bühne</h2>
    <table class="kv">
        @if ($stageSize !== '')<tr><td class="k">Mindestgröße</td><td><strong>{{ $stageSize }}</strong></td></tr>@endif
        @if (filled($rider->stage_notes))<tr><td class="k">Hinweise</td><td>{!! md($rider->stage_notes) !!}</td></tr>@endif
    </table>
</div>
@endif

@if ($stagePlot)
<div class="section">
    <h2>Bühnenplan (Stage Plot)</h2>
    @php($ratio = min(515 / $stagePlot['w'], 520 / $stagePlot['h'], 1))
    <div class="plot"><img src="{{ $stagePlot['src'] }}" width="{{ (int) round($stagePlot['w'] * $ratio) }}" height="{{ (int) round($stagePlot['h'] * $ratio) }}" alt="Bühnenplan"></div>
</div>
@endif

@foreach ($notes as $title => $text)
    @if (filled($text))
        <div class="section">
            <h2>{{ $title }}</h2>
            <p>{!! md($text) !!}</p>
        </div>
    @endif
@endforeach

@foreach ($customSections as $customSection)
    <div class="section">
        <h2>{{ ($customSection['title'] ?? '') ?: 'Weitere Anforderungen' }}</h2>
        <p>{!! md($customSection['content'] ?? '') !!}</p>
    </div>
@endforeach

@if (filled($rider->notes))
<div class="section">
    <h2>Sonstiges</h2>
    <p>{!! md($rider->notes) !!}</p>
</div>
@endif

</body>
</html>
