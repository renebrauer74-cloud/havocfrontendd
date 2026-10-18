@php
    $bioText = trim((string) ($bio[$lang] ?? ''));
    $imageStyle = fn (array $source, string $maxHeight): string => ($source['w'] && $source['h'] / $source['w'] > 0.62) ? "height: {$maxHeight}" : 'width: 100%';
    $hasPhotos = collect($members)->contains(fn (array $member): bool => (bool) $member['photo']);
    $href = fn (string $url): string => preg_match('#^https?://#i', $url) ? $url : 'https://'.$url;
@endphp
<!DOCTYPE html>
<html lang="{{ $lang }}">
<head><meta charset="UTF-8"><style>@include('band-manager.presskit.pdf.style')</style></head>
<body>

@include('band-manager.presskit.pdf.header')

<div class="band">{{ $band->name }}</div>
@if ($band->genre)<div class="genre">{{ $band->genre }}</div>@endif

@if ($cover)
    <div class="cover">
        <img src="{{ $cover['src'] }}" style="{{ $imageStyle($cover, '11cm') }}" alt="">
        @if ($cover['credit'] !== '')<div class="credit">© {{ $cover['credit'] }}</div>@endif
    </div>
@endif

@if ($bioText !== '')
    <h2>{{ $t['bio'] }}</h2>
    <div class="text">{!! md($bioText) !!}</div>
@endif

@if ($members)
    <h2>{{ $t['lineup'] }}</h2>
    <table class="member">
        @foreach ($members as $member)
            <tr>
                @if ($hasPhotos)<td class="ph">@if ($member['photo'])<img src="{{ $member['photo'] }}" alt="">@endif</td>@endif
                <td>
                    <span class="name">{{ $member['name'] }}</span>
                    @if ($member['stage'] !== '')<span class="muted"> {{ $t['quotes'][0] }}{{ $member['stage'] }}{{ $t['quotes'][1] }}</span>@endif
                    @if ($member['role'] !== '')<br><span class="role">{{ $member['role'] }}</span>@endif
                    @if ($member['bio'] !== '')<div class="mbio">{!! md($member['bio']) !!}</div>@endif
                </td>
            </tr>
        @endforeach
    </table>
@endif

@if ($links)
    <div class="section">
        <h2>{{ $t['links'] }}</h2>
        <table class="kv">
            @foreach ($links as $link)
                <tr><td class="k">{{ $link['platform']->label() }}</td><td><a href="{{ $link['url'] }}">{{ $link['display'] }}</a></td></tr>
            @endforeach
        </table>
    </div>
@endif

@if ($contact)
    <div class="section">
        <h2>{{ $t['contact'] }}</h2>
        <div class="box">
            @if (! empty($contact['name']))<div class="name">{{ $contact['name'] }}</div>@endif
            @if (! empty($contact['person']))<div>{{ $contact['person'] }}</div>@endif
            @if (! empty($contact['email']))<div><a href="mailto:{{ $contact['email'] }}">{{ $contact['email'] }}</a></div>@endif
            @if (! empty($contact['phone']))<div><a href="tel:{{ preg_replace('/[^0-9+]/', '', $contact['phone']) }}">{{ $contact['phone'] }}</a></div>@endif
            @if (! empty($contact['website']))<div><a href="{{ $href($contact['website']) }}">{{ $contact['website'] }}</a></div>@endif
        </div>
    </div>
@endif

</body>
</html>
