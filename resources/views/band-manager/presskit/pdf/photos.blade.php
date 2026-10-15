<!DOCTYPE html>
<html lang="{{ $lang }}">
<head><meta charset="UTF-8"><style>@include('band-manager.presskit.pdf.style')</style></head>
<body>

@include('band-manager.presskit.pdf.header')

<div class="title">{{ $band->name }} – {{ $t['photos'] }}</div>
@if ($hint)<p class="muted">{{ $t['photos_hint'] }}</p>@endif

<table class="grid" style="margin-top:10px">
    @foreach (array_chunk($photos, 2) as $row)
        <tr>
            @foreach ($row as $photo)
                <td>
                    <div class="photo">
                        <img src="{{ $photo['src'] }}" style="{{ ($photo['w'] && $photo['h'] / $photo['w'] > 0.75) ? 'height: 7cm' : 'width: 100%' }}" alt="">
                        <div class="cap">
                            @if ($photo['main'])<span class="badge">{{ $photo['label'] }}</span>@else<span class="tag">{{ $photo['label'] }}</span>@endif
                            @if ($photo['caption'] !== '')<br>{{ $photo['caption'] }}@endif
                            @if ($photo['credit'] !== '')<br><span class="muted">© {{ $photo['credit'] }}</span>@endif
                        </div>
                    </div>
                </td>
            @endforeach
            @if (count($row) === 1)<td></td>@endif
        </tr>
    @endforeach
</table>

</body>
</html>
