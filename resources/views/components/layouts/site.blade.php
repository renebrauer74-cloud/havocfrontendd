@props([
    'title',
    'layout' => 'app',
    'styles' => [],
    'backLink' => null,
])
@php
    /** @var array{0: string, 1: string}|null $backLink [URL, Beschriftung] statt der Navigation */
    $navItems = [
        'home' => 'START //',
        'roster' => 'ARTISTS //',
        'calendar' => 'TOUR DATES //',
        'shows.index' => 'SHOWS //',
        'team' => 'TEAM //',
        'stu' => 'STU //',
        'contact' => 'CONTACT //',
    ];
@endphp
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title }}</title>
    <link rel="stylesheet" href="{{ asset('assets/css/base.css') }}">
@foreach ($styles as $style)
    <link rel="stylesheet" href="{{ asset('assets/css/'.$style) }}">
@endforeach
    <script src="{{ asset('assets/js/site.js') }}" defer></script>
</head>
<body class="layout-{{ $layout }}">

<header class="site-header">
    <a href="{{ route('home') }}" class="logo-link">
        <img src="{{ asset('assets/img/havoc-logo.jpeg') }}" alt="HAVOC AGENCY">
    </a>
    <nav>
@if ($backLink)
        <a href="{{ $backLink[0] }}">{{ $backLink[1] }}</a>
@else
@foreach ($navItems as $routeName => $label)
        <a href="{{ route($routeName) }}" @class(['active' => request()->routeIs($routeName)])>{{ $label }}</a>
@endforeach
        <a href="{{ route('admin') }}" class="admin-btn">ADMIN</a>
@endif
    </nav>
</header>

{{ $slot }}

<footer class="site-footer">
    <div class="footer-legal">
        <a href="{{ route('impressum') }}" target="_blank" class="footer-legal-link">IMPRESSUM //</a>
    </div>
    <div class="footer-copy">
        © {{ date('Y') }} HAVOC AGENCY. ALL RIGHTS RESERVED.
    </div>
</footer>
</body>
</html>
