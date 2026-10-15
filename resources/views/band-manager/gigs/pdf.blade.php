{{-- Day Sheet – Vorlage für Dompdf (nur Tabellen + einfaches CSS, kein Flexbox) --}}
@php
    use Carbon\CarbonImmutable;

    $has = function (string ...$fields) use ($gig): bool {
        foreach ($fields as $field) {
            if (filled($gig->{$field})) {
                return true;
            }
        }

        return false;
    };
    // Freitext mit Formatierung (**fett**, *kursiv* …) – sicher escaped
    $text = fn (string $field): string => md((string) $gig->{$field});
    $tel = function (?string $phone): string {
        $phone = trim((string) $phone);

        return $phone === '' ? '' : '<a href="tel:'.e(preg_replace('/[^0-9+]/', '', $phone)).'">'.e($phone).'</a>';
    };
    $row = fn (string $label, string $html): string => trim(strip_tags($html)) !== '' ? '<tr><td class="k">'.e($label).'</td><td>'.$html.'</td></tr>' : '';
    $maps = fn (string $query): string => 'https://www.google.com/maps/search/?api=1&query='.rawurlencode($query);

    $date = CarbonImmutable::createFromFormat('!Y-m-d', $gig->date);
    $dateLabel = ['Sonntag', 'Montag', 'Dienstag', 'Mittwoch', 'Donnerstag', 'Freitag', 'Samstag'][$date->dayOfWeek].', '.$date->format('d.m.Y');

    $venue = $gig->venue ?: $gig->title;
    $address = collect([$gig->venue_street, trim($gig->venue_zip.' '.$gig->city), $gig->venue_country])->filter()->join(', ');

    $showEnd = $gig->time('time_show') !== '' && $gig->set_length
        ? CarbonImmutable::createFromFormat('H:i', $gig->time('time_show'))->addMinutes($gig->set_length)->format('H:i')
        : '';

    $schedule = array_filter([
        'get_in' => ['Get In', $gig->time('time_get_in')],
        'soundcheck' => ['Soundcheck', $gig->time('time_soundcheck')],
        'dinner' => ['Dinner', $gig->time('time_dinner')],
        'doors' => ['Einlass (Doors)', $gig->time('time_doors')],
        'show' => ['Showtime', $gig->time('time_show')],
        'curfew' => ['Curfew', $gig->time('time_curfew')],
    ], fn (array $entry): bool => $entry[1] !== '');

    $keyTime = $gig->time('time_show') !== ''
        ? ['Showtime', $gig->time('time_show').' Uhr']
        : ['Get In', $gig->time('time_get_in') !== '' ? $gig->time('time_get_in').' Uhr' : '–'];
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

    /* Formatierung aus Freitexten (Markdown) */
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
    .facts td    { width: 25%; background: #f4f4f6; padding: 7px 9px; vertical-align: top; }
    .lbl-sm      { font-size: 7px; letter-spacing: 1.5px; text-transform: uppercase; color: #6b6b77; }
    .facts .val  { font-size: 11px; font-weight: bold; color: #0b0b0e; }

    h2 { font-size: 9px; letter-spacing: 2px; text-transform: uppercase; color: #dc2f32;
         border-bottom: 1px solid #e2e2e8; padding-bottom: 3px; margin: 18px 0 6px; }
    .section { page-break-inside: avoid; }

    .kv td   { padding: 3px 0; vertical-align: top; }
    .kv td.k { width: 28%; color: #6b6b77; }

    .sched td         { padding: 5px 9px; border-bottom: 1px solid #ececf0; font-size: 10.5px; }
    .sched td.time    { width: 55px; font-weight: bold; font-size: 12px; }
    .sched tr.show td { background: #0b0b0e; color: #ffffff; }

    .box       { border: 1px solid #e2e2e8; padding: 8px 10px; }
    .box .name { font-weight: bold; font-size: 11px; color: #0b0b0e; }

    .badge { background: #dc2f32; color: #fff; padding: 1px 6px; font-weight: bold; }
    .team th { text-align: left; font-size: 7px; letter-spacing: 1px; text-transform: uppercase; color: #6b6b77;
               padding: 3px 6px; border-bottom: 1px solid #cdcdd5; font-weight: normal; }
    .team td { padding: 4px 6px; border-bottom: 1px solid #ececf0; vertical-align: top; }
    .muted { color: #6b6b77; }
    .link  { color: #dc2f32; font-size: 8.5px; }
</style>
</head>
<body>

<table class="top">
    <tr>
        <td>@if ($logo)<img src="{{ $logo }}" alt="Havoc">@else<span class="brand">HAVOC</span>@endif</td>
        <td class="doc">
            <div class="lbl">DAY SHEET</div>
            <div class="date">{{ $dateLabel }}</div>
        </td>
    </tr>
</table>
<div class="rule"></div>

<div class="title">{{ $band->name }} @ {{ $venue }}</div>
<div class="subtitle">{{ $gig->title }}@if ($gig->city) · {{ $gig->city }}@endif</div>

<table class="facts">
    <tr>
        <td><div class="lbl-sm">Datum</div><div class="val">{{ $date->format('d.m.Y') }}</div></td>
        <td><div class="lbl-sm">Band</div><div class="val">{{ $band->name }}</div></td>
        <td><div class="lbl-sm">Location</div><div class="val">{{ $venue }}</div></td>
        <td><div class="lbl-sm">{{ $keyTime[0] }}</div><div class="val">{{ $keyTime[1] }}</div></td>
    </tr>
</table>

@if ($schedule || $has('schedule_notes'))
<div class="section">
    <h2>Zeitplan</h2>
    <table class="sched">
        @foreach ($schedule as $key => [$label, $time])
            <tr @class(['show' => $key === 'show'])>
                <td class="time">{{ $time }}</td>
                <td>
                    {{ $label }}
                    @if ($key === 'show' && $gig->set_length)
                        &nbsp;– {{ $gig->set_length }} Min.@if ($showEnd) (bis {{ $showEnd }} Uhr)@endif
                    @endif
                </td>
            </tr>
        @endforeach
    </table>
    @if ($has('schedule_notes'))<p class="muted">{!! $text('schedule_notes') !!}</p>@endif
</div>
@endif

<div class="section">
    <h2>Location</h2>
    <table class="kv">
        {!! $row('Venue', '<strong>'.e($venue).'</strong>') !!}
        {!! $row('Adresse', $address !== '' ? e($address).'<br><a class="link" href="'.e($maps($venue.', '.$address)).'">» In Google Maps öffnen</a>' : '') !!}
        {!! $row('Anfahrt / Parken', $text('parking_info')) !!}
    </table>
</div>

@if ($has('contact_venue_name', 'contact_venue_phone', 'contact_tech_name', 'contact_tech_phone'))
<div class="section">
    <h2>Ansprechpartner vor Ort</h2>
    <table>
        <tr>
            <td style="width:50%; padding-right:5px; vertical-align:top;">
                <div class="box">
                    <div class="lbl-sm">Location</div>
                    <div class="name">{{ $gig->contact_venue_name ?: '–' }}</div>
                    {!! $tel($gig->contact_venue_phone) !!}
                </div>
            </td>
            <td style="width:50%; padding-left:5px; vertical-align:top;">
                <div class="box">
                    <div class="lbl-sm">Technik</div>
                    <div class="name">{{ $gig->contact_tech_name ?: '–' }}</div>
                    {!! $tel($gig->contact_tech_phone) !!}
                </div>
            </td>
        </tr>
    </table>
</div>
@endif

@if ($has('hotel_name', 'hotel_address', 'hotel_phone', 'hotel_checkin', 'hotel_checkout', 'hotel_notes'))
<div class="section">
    <h2>Unterkunft</h2>
    <table class="kv">
        {!! $row('Hotel', $gig->hotel_name ? '<strong>'.e($gig->hotel_name).'</strong>' : '') !!}
        {!! $row('Adresse', $gig->hotel_address
            ? e($gig->hotel_address).'<br><a class="link" href="'.e($maps(trim($gig->hotel_name.', '.$gig->hotel_address, ', '))).'">» In Google Maps öffnen</a>' : '') !!}
        {!! $row('Telefon', $tel($gig->hotel_phone)) !!}
        {!! $row('Check-in ab', $gig->time('hotel_checkin') !== '' ? e($gig->time('hotel_checkin')).' Uhr' : '') !!}
        {!! $row('Check-out bis', $gig->time('hotel_checkout') !== '' ? e($gig->time('hotel_checkout')).' Uhr' : '') !!}
        {!! $row('Hinweise', $text('hotel_notes')) !!}
    </table>
</div>
@endif

@if ($has('catering', 'catering_notes'))
<div class="section">
    <h2>Verpflegung</h2>
    <table class="kv">
        {!! $row('Verpflegung', $gig->catering ? '<span class="badge">'.e($catering[$gig->catering] ?? $gig->catering).'</span>' : '') !!}
        {!! $row('Buy-out', $gig->buyout_amount !== null ? e(number_format((float) $gig->buyout_amount, 2, ',', '.')).' € pro Person' : '') !!}
        {!! $row('Hinweise', $text('catering_notes')) !!}
    </table>
</div>
@endif

@if ($has('guestlist', 'wifi', 'backline_info', 'merch_info', 'info_notes'))
<div class="section">
    <h2>Weitere Infos</h2>
    <table class="kv">
        {!! $row('Gästeliste', e((string) $gig->guestlist)) !!}
        {!! $row('WLAN', e((string) $gig->wifi)) !!}
        {!! $row('Backline / Technik', $text('backline_info')) !!}
        {!! $row('Merch', $text('merch_info')) !!}
        {!! $row('Sonstiges', $text('info_notes')) !!}
    </table>
</div>
@endif

@if ($team)
<div class="section">
    <h2>Reisegruppe ({{ count($team) }} Personen)</h2>
    <table class="team">
        <tr><th>Name</th><th>Funktion</th><th>Telefon</th><th>Ernährung / Allergien</th></tr>
        @foreach ($team as $person)
            <tr>
                <td><strong>{{ $person['name'] }}</strong></td>
                <td>{{ $person['role'] }}</td>
                <td>{!! $tel($person['phone']) ?: '<span class="muted">–</span>' !!}</td>
                <td>@if ($person['diet'] !== ''){{ $person['diet'] }}@else<span class="muted">–</span>@endif</td>
            </tr>
        @endforeach
    </table>
</div>
@endif

</body>
</html>
