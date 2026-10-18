@php
    $profileUrl = route('band-manager.team.profile.edit', $band);
    $logoUrl = $profile->hasMedia(\App\Enums\MediaCollectionEnum::BandLogos->value) ? route('band-manager.team.logo', $band) : null;
    $side = $gallery !== null || $logoUrl || $canEdit;
    $bios = array_filter([
        'de' => ['🇩🇪 Deutsch', (string) $profile->bio_de],
        'en' => ['🇬🇧 English', (string) $profile->bio_en],
    ], fn (array $bio): bool => $bio[1] !== '');
@endphp
<x-band-manager.layout :title="'Band/Team – '.$band->name">
    @push('styles')
        <link href="{{ asset('assets/band-manager/css/team.css') }}?v=2" rel="stylesheet">
        <link href="{{ asset('assets/band-manager/css/gallery.css') }}" rel="stylesheet">
    @endpush

    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
        <div>
            @if ($bands->count() > 1)<a href="{{ route('band-manager.team.index') }}" class="hv-back"><i class="bi bi-arrow-left"></i> Alle Bands</a>@endif
            <h1 class="h3 mb-0">{{ $band->name }} – Band &amp; Team</h1>
        </div>
        <div class="d-flex flex-wrap gap-2">
            @if ($bands->count() > 1)
                <x-band-manager.band-switcher :bands="$bands" :current="$band" route="band-manager.team.band" />
            @endif
            @if ($canEdit)
                <a href="{{ route('band-manager.team.create', [$band, 'type' => 'member']) }}" class="btn btn-primary"><i class="bi bi-plus-lg"></i> Bandmitglied</a>
                <a href="{{ route('band-manager.team.create', [$band, 'type' => 'crew']) }}" class="btn btn-outline-primary"><i class="bi bi-plus-lg"></i> Teammitglied</a>
            @endif
        </div>
    </div>

    {{-- ================= Bandprofil ================= --}}
    <div class="card shadow-sm mb-5">
        <div class="card-header tm-profile-head d-flex flex-wrap justify-content-between align-items-center gap-2">
            <h2 class="h5 mb-0"><i class="bi bi-person-vcard"></i> Bandprofil</h2>
            @if ($canEdit)<a href="{{ $profileUrl }}" class="btn btn-primary btn-sm"><i class="bi bi-pencil"></i> Bio, Logo &amp; Links bearbeiten</a>@endif
        </div>
        <div class="card-body">
            <div class="row g-4">
                @if ($side)
                    <div class="col-md-4 col-xl-3">
                        @if ($gallery !== null)
                            @include('band-manager.gallery.profile-media')
                        @endif
                        <div class="small fw-semibold text-muted mt-3 mb-2">Bandlogo</div>
                        @if ($logoUrl)
                            <a href="{{ route('band-manager.team.logo', ['band' => $band, 'download' => 1]) }}" class="tm-logo" title="Logo herunterladen">
                                <img src="{{ $logoUrl }}" alt="Logo {{ $band->name }}">
                            </a>
                        @elseif ($canEdit)
                            <a href="{{ $profileUrl }}" class="tm-logo tm-logo--empty">
                                <span class="tm-logo-empty"><i class="bi bi-plus-lg"></i><br>Logo hochladen</span>
                            </a>
                        @else
                            <div class="tm-logo tm-logo--empty"><span class="tm-logo-empty">Kein Logo</span></div>
                        @endif
                    </div>
                @endif
                <div class="{{ $side ? 'col-md-8 col-xl-9' : 'col-12' }}">

                    @if ($bios)
                        <ul class="nav nav-tabs mb-3" role="tablist">
                            @foreach ($bios as $language => [$label])
                                <li class="nav-item" role="presentation">
                                    <button @class(['nav-link', 'active' => $loop->first]) data-bs-toggle="tab" data-bs-target="#bio-{{ $language }}"
                                            type="button" role="tab">{{ $label }}</button>
                                </li>
                            @endforeach
                        </ul>
                        <div class="tab-content">
                            @foreach ($bios as $language => [, $text])
                                <div @class(['tab-pane fade', 'show active' => $loop->first]) id="bio-{{ $language }}" role="tabpanel">
                                    <div class="tm-bio">{!! md($text) !!}</div>
                                </div>
                            @endforeach
                        </div>
                        @if ($canEdit && count($bios) === 1)
                            <div class="small text-muted mt-2"><i class="bi bi-info-circle"></i>
                                {{ isset($bios['de']) ? 'Die englische Biografie fehlt noch (wichtig fürs internationale Pressekit).' : 'Die deutsche Biografie fehlt noch.' }}
                            </div>
                        @endif
                    @else
                        <div class="tm-empty py-4">
                            Noch keine Biografie hinterlegt.
                            @if ($canEdit)<br><a href="{{ $profileUrl }}" class="btn btn-outline-primary btn-sm mt-2"><i class="bi bi-plus-lg"></i> Jetzt schreiben (Deutsch &amp; Englisch)</a>@endif
                        </div>
                    @endif

                    <div class="tm-section">Social Media &amp; Streaming</div>
                    @if ($links)
                        <div class="tm-socials">
                            @foreach ($links as $link)
                                <a href="{{ $link['url'] }}" target="_blank" rel="noopener" class="tm-social" style="--c: {{ $link['platform']->color() }}"
                                   title="{{ $link['display'] }}">
                                    <span class="tm-social-icon"><i class="bi bi-{{ $link['platform']->icon() }}"></i></span>{{ $link['platform']->label() }}
                                </a>
                            @endforeach
                        </div>
                    @else
                        <span class="text-muted small">Noch keine Links hinterlegt.
                            @if ($canEdit)<a href="{{ $profileUrl }}" class="hv-link">Jetzt eintragen</a>@endif</span>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- ================= Personen ================= --}}
    @foreach ([
        'member' => ['Band', $members, 'Noch keine Bandmitglieder angelegt.'],
        'crew' => ['Team / Crew', $crew, 'Noch keine Teammitglieder (Merch, Ton, Licht, Fahrer …) angelegt.'],
    ] as $type => [$heading, $people, $empty])
        <h2 class="h5 mb-3">{{ $heading }} <span class="tm-count">({{ $people->count() }})</span></h2>
        @if ($people->isNotEmpty())
            <div class="tm-grid mb-5">
                @foreach ($people as $person)
                    @include('band-manager.team.card')
                @endforeach
            </div>
        @else
            <div class="tm-empty mb-5">
                {{ $empty }}
                @if ($canEdit)
                    <br><a href="{{ route('band-manager.team.create', [$band, 'type' => $type]) }}" class="btn btn-outline-primary btn-sm mt-2"><i class="bi bi-plus-lg"></i> Jetzt anlegen</a>
                @endif
            </div>
        @endif
    @endforeach
</x-band-manager.layout>
