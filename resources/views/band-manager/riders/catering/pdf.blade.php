{{-- Catering Rider – Vorlage für Dompdf (nur Tabellen + einfaches CSS, kein Flexbox) --}}
@php
    use App\Enums\BandManager\CateringBuyoutEnum;

    // $people: Reisegruppe, leere Ernährung/Allergie bereits aus „Band/Team“ ergänzt
    $withHints = count($people) - $diets['without'];
    $drinks = $rider->drinks ?? [];
    $customSections = $rider->custom_sections ?? [];
    $buyout = $rider->buyout ?? CateringBuyoutEnum::Catering;

    // Nur die gewünschten Mahlzeiten, in der Reihenfolge des Show-Tages
    $wantedMeals = array_values(array_filter($meals, fn ($meal): bool => (bool) ($rider->meals[$meal->value]['wanted'] ?? false)));

    $euro = fn (mixed $amount): string => number_format((float) $amount, 2, ',', '.').' €';
    $phoneLink = fn (?string $phone): string => filled($phone) ? '<a href="tel:'.e(preg_replace('/[^0-9+]/', '', $phone)).'">'.e($phone).'</a>' : '';
    $emailLink = fn (?string $email): string => filled($email) ? '<a href="mailto:'.e($email).'">'.e($email).'</a>' : '';

    $contact = array_filter([
        'Name' => e((string) $rider->contact_name),
        'Telefon' => $phoneLink($rider->contact_phone),
        'E-Mail' => $emailLink($rider->contact_email),
    ], fn (string $html): bool => $html !== '');

    $dressing = array_filter([
        'Garderoben' => $rider->dressing_rooms !== null ? $rider->dressing_rooms.' '.($rider->dressing_rooms === 1 ? 'Raum' : 'Räume') : '',
        'Bühnenhandtücher' => $rider->stage_towels !== null ? $rider->stage_towels.' Stück' : '',
        'Duschhandtücher' => $rider->shower_towels !== null ? $rider->shower_towels.' Stück' : '',
        'Dusche' => $rider->needs_shower ? 'wird benötigt' : '',
    ], fn (string $text): bool => $text !== '');

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
    .facts .s { font-size: 11px; font-weight: bold; color: #0b0b0e; padding: 2px 0; }
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
    .grid td.k   { width: 30%; background: #f3f3f6; font-weight: bold; }
    .grid tr.group td { background: #e7e7ec; font-weight: bold; text-transform: uppercase; letter-spacing: 1px; font-size: 7.5px; color: #0b0b0e; }
    .grid td.n   { width: 40px; text-align: center; font-weight: bold; font-size: 11px; color: #dc2f32; }
    .diet  { font-weight: bold; color: #dc2f32; }
    .muted { color: #6b6b77; }

    .text { border: 1px solid #9d9da8; padding: 7px 9px; }
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
    <div class="doc">CATERING RIDER</div>
    <div class="meta">{{ $band->name }}@if ($band->genre) · {{ $band->genre }}@endif · Stand {{ $rider->updated_at?->format('d.m.Y') }}</div>
</div>
<div class="stripe"></div>

<table class="facts">
    <tr>
        <td><div class="v">{{ count($people) ?: '–' }}</div><div class="l">Personen</div></td>
        <td><div class="v">{{ $withHints ?: '–' }}</div><div class="l">mit Ernährung / Allergie</div></td>
        <td><div class="s">{{ $buyout->label() }}</div><div class="l">Verpflegung</div></td>
        <td><div class="v">{{ $buyout->hasBuyout() && $rider->buyout_amount !== null ? $euro($rider->buyout_amount) : (count($wantedMeals) ?: '–') }}</div>
            <div class="l">{{ $buyout->hasBuyout() && $rider->buyout_amount !== null ? 'Buyout pro Person' : 'Mahlzeiten' }}</div></td>
    </tr>
</table>

{{-- ================= Kontakt ================= --}}
@if ($contact)
<div class="block">
    {!! $heading('Ansprechpartner') !!}
    <table class="grid">
        @foreach ($contact as $label => $html)
            <tr><td class="k">{{ $label }}</td><td>{!! $html !!}</td></tr>
        @endforeach
    </table>
</div>
@endif

{{-- ================= Ernährung auf einen Blick ================= --}}
@if ($people || filled($rider->diet_notes))
<div class="block">
    {!! $heading('Ernährung & Allergien', 'auf einen Blick') !!}
    @if ($people)
        <table class="grid">
            <thead><tr><th style="width:40px">Anzahl</th><th style="width:38%">Ernährung / Allergie</th><th>Personen</th></tr></thead>
            @if ($diets['allergies'])
                <tr class="group"><td colspan="3">Allergien – bitte unbedingt beachten</td></tr>
                @foreach ($diets['allergies'] as $group)
                    <tr>
                        <td class="n">{{ $group['count'] }} ×</td>
                        <td><span class="diet">{{ $group['label'] }}</span></td>
                        <td>{{ implode(', ', array_filter($group['names'])) }}</td>
                    </tr>
                @endforeach
            @endif
            @if ($diets['diets'])
                <tr class="group"><td colspan="3">Ernährung</td></tr>
                @foreach ($diets['diets'] as $group)
                    <tr>
                        <td class="n">{{ $group['count'] }} ×</td>
                        <td><strong>{{ $group['label'] }}</strong></td>
                        <td>{{ implode(', ', array_filter($group['names'])) }}</td>
                    </tr>
                @endforeach
            @endif
            @if ($diets['without'] > 0)
                <tr class="group"><td colspan="3">Ohne Einschränkungen</td></tr>
                <tr><td class="n" style="color:#1b1b20">{{ $diets['without'] }} ×</td><td colspan="2">isst alles</td></tr>
            @endif
        </table>
    @endif
    @if (filled($rider->diet_notes))
        <div class="text" @if ($people) style="margin-top:6px" @endif>{!! md($rider->diet_notes) !!}</div>
    @endif
</div>
@endif

{{-- ================= Reisegruppe ================= --}}
@if ($people)
<div class="block">
    {!! $heading('Reisegruppe', count($people).' Personen') !!}
    <table class="grid">
        <thead><tr><th style="width:26px">Nr.</th><th style="width:28%">Name</th><th style="width:22%">Funktion</th><th style="width:20%">Ernährung</th><th>Allergien</th></tr></thead>
        @foreach ($people as $i => $person)
            <tr @class(['alt' => $i % 2 === 1])>
                <td class="ch">{{ $i + 1 }}</td>
                <td><strong>{{ $person['name'] ?? '' }}</strong></td>
                <td>{{ $person['role'] ?? '' }}</td>
                <td>@if (filled($person['diet'] ?? null)){{ $person['diet'] }}@else<span class="muted">–</span>@endif</td>
                <td>@if (filled($person['allergy'] ?? null))<span class="diet">{{ $person['allergy'] }}</span>@else<span class="muted">–</span>@endif</td>
            </tr>
        @endforeach
    </table>
</div>
@endif

{{-- ================= Mahlzeiten ================= --}}
@if ($wantedMeals)
<div class="block">
    {!! $heading('Mahlzeiten') !!}
    <table class="grid">
        <thead><tr><th style="width:22%">Mahlzeit</th><th style="width:24%">Wann</th><th>Wünsche</th></tr></thead>
        @foreach ($wantedMeals as $i => $meal)
            @php($values = $rider->meals[$meal->value])
            <tr @class(['alt' => $i % 2 === 1])>
                <td><strong>{{ $meal->label() }}</strong></td>
                <td>{{ $values['time'] ?? '' }}</td>
                <td>{!! filled($values['wishes'] ?? null) ? md($values['wishes']) : '' !!}</td>
            </tr>
        @endforeach
    </table>
</div>
@endif

{{-- ================= Buyout ================= --}}
<div class="block">
    {!! $heading('Catering / Buyout') !!}
    <table class="grid">
        <tr><td class="k">Verpflegung</td><td><strong>{{ $buyout->label() }}</strong> – {{ $buyout->description() }}</td></tr>
        @if ($buyout->hasBuyout() && $rider->buyout_amount !== null)
            <tr><td class="k">Buyout pro Person</td><td><strong>{{ $euro($rider->buyout_amount) }}</strong>@if ($people) · bei {{ count($people) }} Personen {{ $euro($rider->buyout_amount * count($people)) }}@endif</td></tr>
        @endif
        @if (filled($rider->buyout_notes))
            <tr><td class="k">Hinweise</td><td>{!! md($rider->buyout_notes) !!}</td></tr>
        @endif
    </table>
</div>

{{-- ================= Getränke ================= --}}
@if ($drinks || filled($rider->drink_notes))
<div class="block">
    {!! $heading('Getränke', $drinks ? count($drinks).' Positionen' : '') !!}
    @if ($drinks)
        <table class="grid">
            <thead><tr><th style="width:26px">Nr.</th><th style="width:42%">Getränk</th><th style="width:20%">Menge</th><th>Bemerkung</th></tr></thead>
            @foreach ($drinks as $i => $drink)
                <tr @class(['alt' => $i % 2 === 1])>
                    <td class="ch">{{ $i + 1 }}</td>
                    <td><strong>{{ $drink['item'] ?? '' }}</strong></td>
                    <td>{{ $drink['amount'] ?? '' }}</td>
                    <td>{{ $drink['note'] ?? '' }}</td>
                </tr>
            @endforeach
        </table>
    @endif
    @if (filled($rider->drink_notes))
        <div class="text" @if ($drinks) style="margin-top:6px" @endif>{!! md($rider->drink_notes) !!}</div>
    @endif
</div>
@endif

{{-- ================= Garderobe ================= --}}
@if ($dressing || filled($rider->dressing_room_notes))
<div class="block">
    {!! $heading('Garderobe & Backstage') !!}
    <table class="grid">
        @foreach ($dressing as $label => $text)
            <tr><td class="k">{{ $label }}</td><td><strong>{{ $text }}</strong></td></tr>
        @endforeach
        @if (filled($rider->dressing_room_notes))
            <tr><td class="k">Ausstattung</td><td>{!! md($rider->dressing_room_notes) !!}</td></tr>
        @endif
    </table>
</div>
@endif

{{-- ================= Eigene Abschnitte ================= --}}
@foreach ($customSections as $customSection)
    <div class="block">
        {!! $heading(($customSection['title'] ?? '') ?: 'Weitere Wünsche') !!}
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
