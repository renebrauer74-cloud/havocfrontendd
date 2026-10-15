<!DOCTYPE html>
<html lang="{{ $lang }}">
<head><meta charset="UTF-8"><style>@include('band-manager.presskit.pdf.style')</style></head>
<body>

@include('band-manager.presskit.pdf.header')

<div class="title">{{ $band->name }} – {{ $t['live'] }}</div>

@foreach (['upcoming' => $upcoming, 'past' => $past] as $key => $rows)
    @continue($rows === null)
    <h2>{{ $t[$key] }}@if ($key === 'past') ({{ $months }} {{ $t['months'] }})@endif</h2>
    @if ($rows)
        <table class="list">
            <tr><th>{{ $t['date'] }}</th><th>{{ $t['city'] }}</th><th>{{ $t['venue'] }}</th><th>{{ $t['event'] }}</th></tr>
            @foreach ($rows as $row)
                <tr>
                    <td class="d">{{ $row['date'] }}</td>
                    <td>{{ $row['city'] ?: '–' }}</td>
                    <td>{{ $row['venue'] ?: '–' }}</td>
                    <td>{{ $row['event'] }}</td>
                </tr>
            @endforeach
        </table>
    @else
        <p class="muted">{{ $t['no_gigs'] }}</p>
    @endif
@endforeach

</body>
</html>
