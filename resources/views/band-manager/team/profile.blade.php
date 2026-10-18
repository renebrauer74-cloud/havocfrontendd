@php
    $back = route('band-manager.team.band', $band);
    $logoUrl = $profile->hasMedia(\App\Enums\MediaCollectionEnum::BandLogos->value) ? route('band-manager.team.logo', $band) : null;
    $languages = [
        'de' => ['🇩🇪 Deutsch', 'Wer seid ihr, wie klingt ihr, was habt ihr erreicht? (Releases, Festivals, Support-Shows …)'],
        'en' => ['🇬🇧 English', 'Who are you, what do you sound like, what have you achieved? (releases, festivals, support shows …)'],
    ];
@endphp
<x-band-manager.layout :title="'Bandprofil – '.$band->name">
    @push('styles')
        <link href="{{ asset('assets/band-manager/css/team.css') }}?v=2" rel="stylesheet">
    @endpush

    <a href="{{ $back }}" class="hv-back"><i class="bi bi-arrow-left"></i> {{ $band->name }} – Band &amp; Team</a>
    <h1 class="h3 mb-1">Bandprofil bearbeiten</h1>
    <p class="text-muted small mb-4">Logo, Biografie und Links erscheinen im Bandprofil und im <strong>Pressekit</strong>.</p>

    <form method="post" action="{{ route('band-manager.team.profile.update', $band) }}" enctype="multipart/form-data"
          class="card card-body shadow-sm" style="max-width:1000px">
        @csrf
        @method('PUT')

        {{-- ================= Bandlogo ================= --}}
        <div class="tm-section tm-section--first">Bandlogo</div>
        <div class="row g-4 align-items-start">
            <div class="col-md-5">
                <div class="tm-logo tm-logo--form" id="tmLogoPreview">
                    @if ($logoUrl)
                        <img src="{{ $logoUrl }}" alt="Logo {{ $band->name }}">
                    @else
                        <span class="tm-logo-empty"><i class="bi bi-image"></i><br>Noch kein Logo</span>
                    @endif
                </div>
            </div>
            <div class="col-md-7">
                <label class="form-label" for="tmLogoInput">Logo hochladen</label>
                <input type="file" name="logo" id="tmLogoInput" class="form-control" accept="image/png,image/jpeg,image/webp">
                <div class="form-text">
                    Am besten <strong>PNG mit transparentem Hintergrund</strong> · auch JPG oder WebP · max. {{ $logoMaxMb }} MB.
                    Das Logo wird unverändert gespeichert (keine Verkleinerung).
                </div>
                @if ($logoUrl)
                    <div class="d-flex flex-wrap align-items-center gap-3 mt-2">
                        <a href="{{ route('band-manager.team.logo', ['band' => $band, 'download' => 1]) }}" class="small hv-link"><i class="bi bi-download"></i> Logo herunterladen</a>
                        <div class="form-check m-0">
                            <input class="form-check-input" type="checkbox" name="remove_logo" value="1" id="remove_logo">
                            <label class="form-check-label small" for="remove_logo">Logo entfernen</label>
                        </div>
                    </div>
                @endif
            </div>
        </div>

        {{-- ================= Biografie ================= --}}
        <div class="tm-section">Biografie</div>
        <div class="row g-3">
            @foreach ($languages as $language => [$label, $placeholder])
                <div class="col-lg-6">
                    <label class="form-label" for="bio_{{ $language }}">{{ $label }}</label>
                    <textarea name="bio_{{ $language }}" id="bio_{{ $language }}" class="form-control" rows="14" maxlength="{{ $bioMax }}"
                              placeholder="{{ $placeholder }}" data-md data-counter="cnt_{{ $language }}">{{ old('bio_'.$language, $profile->{'bio_'.$language}) }}</textarea>
                    <div class="form-text"><span id="cnt_{{ $language }}">0</span> / {{ number_format($bioMax, 0, ',', '.') }} Zeichen</div>
                </div>
            @endforeach
        </div>

        {{-- ================= Links ================= --}}
        <div class="tm-section">Social Media &amp; Streaming</div>
        <p class="form-text mt-0 mb-3">Kompletten Link einfügen – bei Instagram, TikTok, YouTube, X und Threads reicht auch <code>@name</code>. Leere Felder werden nicht angezeigt.</p>
        <div class="row g-3">
            @foreach ($platforms as $platform)
                @php($key = $platform->value)
                <div class="col-md-6">
                    <label class="form-label" for="link_{{ $key }}">{{ $platform->label() }}</label>
                    <div class="input-group">
                        <span class="input-group-text tm-platform-icon" style="--c: {{ $platform->color() }}"><i class="bi bi-{{ $platform->icon() }}"></i></span>
                        <input type="text" name="link_{{ $key }}" id="link_{{ $key }}" class="form-control" maxlength="255"
                               placeholder="{{ $platform->placeholder() }}"
                               value="{{ old('link_'.$key, $profile->links[$key] ?? '') }}">
                    </div>
                </div>
            @endforeach
        </div>

        <div class="mt-4 d-flex gap-2">
            <button class="btn btn-primary">Speichern</button>
            <a href="{{ $back }}" class="btn btn-secondary">Abbrechen</a>
        </div>
    </form>

    @push('scripts')
        <script>
            document.querySelectorAll('textarea[data-counter]').forEach(t => {
                const out = document.getElementById(t.dataset.counter);
                const update = () => out.textContent = t.value.length.toLocaleString('de-DE');
                t.addEventListener('input', update);
                update();
            });

            // Vorschau des gewählten Logos
            document.getElementById('tmLogoInput')?.addEventListener('change', e => {
                const file = e.target.files[0];
                if (!file) return;
                const img = new Image();
                img.src = URL.createObjectURL(file);
                document.getElementById('tmLogoPreview').replaceChildren(img);
            });
        </script>
    @endpush
</x-band-manager.layout>
