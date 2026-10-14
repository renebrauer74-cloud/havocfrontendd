@props(['title'])
@php
    $sections = [
        'admin.artists' => 'Künstler',
        'admin.dates' => 'Tourdaten',
        'admin.gallery' => 'Bildergalerie',
        'admin.news' => 'News',
        'admin.team' => 'Team',
        'admin.shows' => 'Shows (Agentur)',
        'admin.messages' => 'Anfragen',
    ];
@endphp
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex">
    <title>HAVOC ADMIN | {{ $title }}</title>
    <link rel="stylesheet" href="{{ asset('assets/css/admin.css') }}">
    <script src="{{ asset('assets/js/admin.js') }}" defer></script>
</head>
<body class="admin-dashboard">

    <aside>
        <a class="sidebar-logo" href="{{ route('home') }}" title="Zur Webseite">HAVOC</a>
        <nav class="sidebar-menu">
@foreach ($sections as $routePrefix => $label)
            <a @class(['menu-item', 'active' => request()->routeIs($routePrefix.'.*')]) href="{{ route($routePrefix.'.index') }}">{{ $label }}</a>
@endforeach
        </nav>
        <form method="post" action="{{ route('admin.logout') }}" class="sidebar-logout-form">
            @csrf
            <button type="submit" class="sidebar-logout">LOGOUT</button>
        </form>
    </aside>

    <div class="content-area">
@if ($errors->any())
        <div class="status-message is-error">{{ implode(' ', $errors->all()) }}</div>
@elseif (session('status'))
        <div class="status-message is-success">{{ session('status') }}</div>
@endif

        {{ $slot }}
    </div>

</body>
</html>
