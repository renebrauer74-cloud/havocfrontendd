@props(['title' => null])
@php
    use App\Models\BandManager\User;
    use App\Services\BandManager\Impersonation;
    use App\Services\BandManager\Navigation;

    $user = auth('band-manager')->user();
    $isImpersonating = app(Impersonation::class)->isActive();
    $appName = config('band-manager.name');
    $logo = asset('assets/band-manager/img/havoc-logo.png');

    // Design: Benutzer-Einstellung → Cookie (auch für die Login-Seite) → Standard
    $theme = $user?->theme && ! $isImpersonating ? $user->theme : request()->cookie('hv_theme');
    $theme = in_array($theme, User::THEMES, true) ? $theme : config('band-manager.default_theme');
    $toggleIcon = $theme === 'dark' ? 'sun' : 'moon-stars';

    $navigation = $user ? app(Navigation::class)->for($user) : [];
    $mainNavigation = array_filter($navigation, fn (array $item): bool => $item['section'] === 'main');
    $adminNavigation = array_filter($navigation, fn (array $item): bool => $item['section'] === 'admin');
    $unreadTotal = array_sum(array_column($navigation, 'badge'));

    // Meldungen (Erfolg, Fehler …) mit Icon
    $flashTypes = ['success' => ['success', 'check-circle'], 'error' => ['danger', 'exclamation-octagon'], 'warning' => ['warning', 'exclamation-triangle'], 'info' => ['info', 'info-circle']];
    $validationErrors = isset($errors) ? $errors->all() : [];
@endphp
<!doctype html>
<html lang="de" data-bs-theme="{{ $theme }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title>{{ $title ?? $appName }} · {{ $appName }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="{{ asset('assets/band-manager/css/app.css') }}" rel="stylesheet">
    <link href="{{ asset('assets/band-manager/css/havoc.css') }}?v=8" rel="stylesheet">
    @stack('styles')
</head>
<body class="hv-body">

@if ($user)

    {{-- Kopfzeile nur auf dem Handy --}}
    <header class="hv-topbar d-md-none">
        <a href="{{ route('band-manager.index') }}" class="hv-logo-box hv-logo-box--sm"><img src="{{ $logo }}" alt="{{ $appName }}"></a>
        <div class="d-flex gap-2">
            <button class="hv-icon-btn" type="button" data-hv-theme-toggle aria-label="Hell/Dunkel wechseln">
                <i class="bi bi-{{ $toggleIcon }}"></i>
            </button>
            <button class="hv-icon-btn" type="button" data-bs-toggle="offcanvas" data-bs-target="#hvSidebar"
                    aria-controls="hvSidebar" aria-label="Menü öffnen">
                <i class="bi bi-list"></i>
                @if ($unreadTotal)<span class="hv-burger-dot"></span>@endif
            </button>
        </div>
    </header>

    {{-- Seitenleiste: PC/Tablet fest links, Handy ausklappbar --}}
    <aside class="hv-sidebar offcanvas-md offcanvas-start" tabindex="-1" id="hvSidebar" aria-label="Hauptmenü">
        <div class="hv-sidebar-inner">

            <div class="hv-sidebar-head">
                <a href="{{ route('band-manager.index') }}" class="hv-logo-box"><img src="{{ $logo }}" alt="{{ $appName }}"></a>
                <button type="button" class="btn-close d-md-none" data-bs-dismiss="offcanvas"
                        data-bs-target="#hvSidebar" aria-label="Schließen"></button>
            </div>

            <nav class="hv-side-nav">
                <div class="hv-side-label">Menü</div>
                @foreach ($mainNavigation as $item)
                    <x-band-manager.nav-link :item="$item" />
                @endforeach

                @if ($adminNavigation)
                    <div class="hv-side-section">
                        <div class="hv-side-label"><i class="bi bi-shield-lock"></i> Administration</div>
                        @foreach ($adminNavigation as $item)
                            <x-band-manager.nav-link :item="$item" />
                        @endforeach
                    </div>
                @endif
            </nav>

            {{-- Hell / Dunkel --}}
            <div class="hv-side-theme">
                <div class="hv-theme-switch" role="group" aria-label="Design wählen">
                    @foreach (['light' => ['sun', 'Hell'], 'dark' => ['moon-stars', 'Dunkel']] as $key => [$icon, $label])
                        <button type="button" data-hv-theme-set="{{ $key }}" @class(['active' => $theme === $key])
                                aria-pressed="{{ $theme === $key ? 'true' : 'false' }}">
                            <i class="bi bi-{{ $icon }}"></i><span>{{ $label }}</span>
                        </button>
                    @endforeach
                </div>
            </div>

            <div class="hv-side-foot">
                <div class="hv-user-chip">
                    <span class="hv-avatar">{{ mb_strtoupper(mb_substr($user->name, 0, 1)) }}</span>
                    <span class="hv-user-text">
                        <span class="hv-user-name">{{ $user->name }}</span>
                        <span class="hv-user-role">{{ $user->role->label() }}</span>
                    </span>
                </div>
                <form method="post" action="{{ route('band-manager.logout') }}" class="m-0">
                    @csrf
                    <button class="hv-logout" title="Abmelden" aria-label="Abmelden"><i class="bi bi-box-arrow-right"></i></button>
                </form>
            </div>

        </div>
    </aside>

    <div class="hv-main">
        @if ($isImpersonating)
            <div class="hv-impersonate">
                <span><i class="bi bi-eye"></i> Du siehst die Ansicht von <strong>{{ $user->name }}</strong>.</span>
                <form method="post" action="{{ route('band-manager.impersonation.destroy') }}" class="m-0">
                    @csrf
                    @method('DELETE')
                    <button class="btn btn-sm btn-light">Zurück zum Admin</button>
                </form>
            </div>
        @endif

        <main class="hv-content">
            <x-band-manager.flash :types="$flashTypes" :validation-errors="$validationErrors" />
            {{ $slot }}
        </main>

        <footer class="hv-footer">© {{ date('Y') }} Havoc Agency · All rights reserved</footer>
    </div>

@else

    <button class="hv-icon-btn hv-guest-toggle" type="button" data-hv-theme-toggle aria-label="Hell/Dunkel wechseln">
        <i class="bi bi-{{ $toggleIcon }}"></i>
    </button>

    <main class="hv-guest">
        <div class="hv-guest-brand">
            <span class="hv-logo-box hv-logo-box--lg"><img src="{{ $logo }}" alt="{{ $appName }}"></span>
            <p class="hv-claim">We wreak <span>havoc.</span></p>
            <p class="hv-sub">Artist Booking &amp; Management</p>
        </div>
        <div class="container">
            <div class="mx-auto" style="max-width:400px">
                <x-band-manager.flash :types="$flashTypes" :validation-errors="$validationErrors" />
            </div>
            {{ $slot }}
        </div>
    </main>

@endif

<script>
    window.HV_THEME = @json([
        'saveUrl' => $user ? route('band-manager.theme.update') : null,
        'csrf' => csrf_token(),
    ]);
</script>
<script src="{{ asset('assets/band-manager/js/theme.js') }}?v=2"></script>
@if ($user)
    <template id="hvMdLegend">{!! md_legend() !!}</template>
    <script src="{{ asset('assets/band-manager/js/markdown.js') }}?v=1"></script>
@endif
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
@stack('scripts')
</body>
</html>
