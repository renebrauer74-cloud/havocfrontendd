{{-- Stage Plot – Vorlage für Dompdf, A4 quer (nur Tabellen + einfaches CSS, kein Flexbox) --}}
@php
    $meters = fn (mixed $value): string => str_replace('.', ',', (string) (float) $value).' m';

    // Logo höchstens 200 × 56 pt, Bild höchstens 755 × 435 pt (füllt die erste Seite) – Seitenverhältnis bleibt
    $logoScale = $bandLogo ? min(200 / $bandLogo['w'], 56 / $bandLogo['h'], 1) : 1;
    $imageScale = $image ? min(755 / $image['w'], 435 / $image['h'], 1) : 1;
@endphp
<!DOCTYPE html>
<html lang="de">
<head>
<meta charset="UTF-8">
<style>
    @page { margin: 26px 36px 44px 36px; }
    body  { font-family: "DejaVu Sans", sans-serif; font-size: 9px; color: #1b1b20; line-height: 1.4; }
    table { width: 100%; border-collapse: collapse; }

    strong { font-weight: bold; }
    em     { font-style: italic; }
    u      { text-decoration: underline; }
    s      { text-decoration: line-through; }

    /* Kopf: Bandlogo links, Titel rechts */
    .top td      { vertical-align: middle; }
    .top .band   { font-size: 22px; font-weight: bold; letter-spacing: 2px; color: #0b0b0e; }
    .top .doc    { font-size: 10px; font-weight: bold; letter-spacing: 6px; color: #0b0b0e; text-align: right; }
    .top .meta   { margin-top: 2px; font-size: 8px; color: #6b6b77; text-align: right; }
    .stripe      { height: 4px; background: #0b0b0e; border-bottom: 2px solid #dc2f32; margin: 8px 0 10px; }

    .plot  { border: 1px solid #9d9da8; padding: 6px; text-align: center; }
    .empty { padding: 40px; color: #6b6b77; }

    .head        { margin: 14px 0 6px; }
    .head td     { background: #0b0b0e; color: #ffffff; padding: 5px 8px; font-weight: bold; letter-spacing: 2px; text-transform: uppercase; font-size: 8.5px; }
    .block       { page-break-inside: avoid; }

    .grid th { background: #2a2a31; color: #ffffff; text-align: left; font-size: 7px; letter-spacing: 1px;
               text-transform: uppercase; padding: 4px 6px; border: 1px solid #2a2a31; font-weight: bold; }
    .grid td { border: 1px solid #9d9da8; padding: 4px 6px; vertical-align: top; }
    .grid tr.alt td { background: #f3f3f6; }
    .grid td.ch { width: 26px; text-align: center; font-weight: bold; background: #e7e7ec; }
    .split td.half { width: 50%; vertical-align: top; }
    .text { border: 1px solid #9d9da8; padding: 7px 9px; }
</style>
</head>
<body>

<table class="top">
    <tr>
        <td>
            @if ($bandLogo)
                <img src="{{ $bandLogo['src'] }}" width="{{ (int) round($bandLogo['w'] * $logoScale) }}" height="{{ (int) round($bandLogo['h'] * $logoScale) }}" alt="{{ $band->name }}">
            @else
                <div class="band">{{ $band->name }}</div>
            @endif
        </td>
        <td>
            <div class="doc">STAGE PLOT</div>
            <div class="meta">{{ $band->name }} · Bühne mind. {{ $meters($plot->stage_width) }} × {{ $meters($plot->stage_depth) }} · Stand {{ $plot->updated_at?->format('d.m.Y') }}</div>
        </td>
    </tr>
</table>
<div class="stripe"></div>

<div class="plot">
    @if ($image)
        <img src="{{ $image['src'] }}" width="{{ (int) round($image['w'] * $imageScale) }}" height="{{ (int) round($image['h'] * $imageScale) }}" alt="Stage Plot {{ $band->name }}">
    @else
        <div class="empty">Kein Bild vorhanden – bitte den Stage Plot im Rider Creator erneut speichern.</div>
    @endif
</div>

@if ($legend || filled($plot->notes))
<table class="split" style="margin-top:4px">
    <tr>
        @if ($legend)
            <td class="half block" style="padding-right:4px;">
                <table class="head"><tr><td>Legende</td></tr></table>
                <table class="grid">
                    <thead><tr><th style="width:26px">Nr.</th><th style="width:40%">Element</th><th>Beschriftung</th></tr></thead>
                    @foreach ($legend as $i => $entry)
                        <tr @class(['alt' => $i % 2 === 1])>
                            <td class="ch">{{ $i + 1 }}</td>
                            <td>{{ $entry['element'] }}</td>
                            <td><strong>{{ $entry['label'] }}</strong></td>
                        </tr>
                    @endforeach
                </table>
            </td>
        @endif
        @if (filled($plot->notes))
            <td class="half block" style="padding-left:4px;">
                <table class="head"><tr><td>Hinweise</td></tr></table>
                <div class="text">{!! md($plot->notes) !!}</div>
            </td>
        @endif
    </tr>
</table>
@endif

</body>
</html>
