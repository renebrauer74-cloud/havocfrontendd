@inject('checkAccess', 'App\Actions\BandManager\Cloud\CheckCloudLinkAccessAction')
@php
    $expiryDays = (array) config('band-manager.cloud.link_expiry_days');
    $selectedExpiry = old('expires_in', $expiryDays[intdiv(count($expiryDays), 2)] ?? 0);
    $selectedParent = old('parent_id', $defaultParentId);
    $showOptions = $errors->hasAny(['parent_id', 'message', 'expires_in']);
@endphp
<x-band-manager.layout :title="'Photo Drop – '.$band->name">
    @push('styles')
        <link href="{{ asset('assets/band-manager/css/cloud.css') }}?v=5" rel="stylesheet">
    @endpush

    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <div>
            @if ($bands->count() > 1)<a href="{{ route('band-manager.photo-drops.index') }}" class="hv-back"><i class="bi bi-arrow-left"></i> Alle Bands</a>@endif
            <h1 class="h3 mb-0">{{ $band->name }} – Photo Drop</h1>
        </div>
        @if ($bands->count() > 1)
            <x-band-manager.band-switcher :bands="$bands" :current="$band" route="band-manager.photo-drops.band" />
        @endif
    </div>

    <p class="text-muted small">
        <i class="bi bi-info-circle"></i> Name eingeben, Link erstellen, teilen – fertig. Wer den Link hat (z. B. über Social Media oder
        WhatsApp), lädt ohne Login Fotos &amp; Videos in einen eigenen Ordner in eurem Speicher. Löschen kann darüber niemand.
    </p>

    {{-- Neuer Photo Drop --}}
    <form method="post" action="{{ route('band-manager.photo-drops.store', $band) }}" class="card card-body shadow-sm mb-4">
        @csrf
        <label class="form-label fw-semibold" for="pdName">Neuer Photo Drop</label>
        <div class="input-group">
            <span class="input-group-text"><i class="bi bi-camera"></i></span>
            <input name="name" id="pdName" @class(['form-control', 'is-invalid' => $errors->has('name')]) maxlength="{{ config('band-manager.cloud.name_max') }}" required
                   placeholder="z. B. Gig Gloria Köln 14.11." value="{{ old('name') }}">
            <button class="btn btn-primary"><i class="bi bi-link-45deg"></i> Link erstellen</button>
        </div>
        <div class="form-text">Unter diesem Namen legen wir den Ordner an, in dem die Fotos landen.</div>

        <a class="small mt-2 hv-link" data-bs-toggle="collapse" href="#pdOptions" role="button" aria-expanded="{{ $showOptions ? 'true' : 'false' }}" aria-controls="pdOptions">
            <i class="bi bi-sliders"></i> Weitere Optionen
        </a>
        <div @class(['collapse', 'show' => $showOptions]) id="pdOptions">
            <div class="row g-3 pt-3">
                <div class="col-md-6">
                    <label class="form-label" for="pdParent">Ordner anlegen in</label>
                    <select name="parent_id" id="pdParent" class="form-select">
                        <option value="">Speicher (oberste Ebene)</option>
                        @foreach ($tree as ['folder' => $option, 'depth' => $depth])
                            <option value="{{ $option->id }}" @selected((string) $selectedParent === (string) $option->id)>
                                {!! str_repeat('&nbsp;&nbsp;&nbsp;', $depth) !!}{{ $option->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label" for="pdExpires">Link gültig</label>
                    <select name="expires_in" id="pdExpires" class="form-select">
                        @foreach ($expiryDays as $days)
                            <option value="{{ $days }}" @selected((int) $selectedExpiry === (int) $days)>{{ $days }} Tage</option>
                        @endforeach
                        <option value="0" @selected((int) $selectedExpiry === 0)>Unbegrenzt</option>
                    </select>
                </div>
                <div class="col-12">
                    <label class="form-label" for="pdMessage">Hinweis für die Besucher <span class="text-muted small">(optional)</span></label>
                    <textarea name="message" id="pdMessage" class="form-control" rows="2" maxlength="500"
                              placeholder="z. B. Danke fürs Kommen! Ladet hier eure Fotos vom Gig im Gloria hoch.">{{ old('message') }}</textarea>
                </div>
                <div class="col-12">
                    <div class="form-check">
                        <input type="hidden" name="allow_all_types" value="0">
                        <input class="form-check-input" type="checkbox" name="allow_all_types" value="1" id="pdAllTypes" @checked(old('allow_all_types'))>
                        <label class="form-check-label" for="pdAllTypes">
                            Alle Dateitypen erlauben <span class="text-muted small">(sonst nur Fotos &amp; Videos; Programme nie)</span>
                        </label>
                    </div>
                </div>
            </div>
        </div>
    </form>

    {{-- Bestehende Photo Drops --}}
    <h2 class="h5 mb-3">Eure Photo Drops</h2>
    <div class="d-flex flex-column gap-3">
        @forelse ($drops as $drop)
            @php
                $url = route('band-manager.share.show', $drop);
                $usable = $checkAccess->execute($drop);
            @endphp
            <div @class(['card card-body shadow-sm', 'cl-link-new' => $drop->id === $newLinkId])>
                <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-2">
                    <span class="cl-name">
                        <span class="cl-icon cl-icon--folder"><i class="bi bi-camera"></i></span>
                        <span>
                            <span class="fw-semibold">{{ $drop->folder?->name ?? 'Ordner im Papierkorb' }}</span>
                            <span class="d-block small text-muted">
                                <i class="bi bi-images"></i> {{ $drop->uploads_count }} {{ $drop->uploads_count === 1 ? 'Datei' : 'Dateien' }} hochgeladen
                            </span>
                        </span>
                        @if ($drop->isExpired())
                            <span class="badge text-bg-secondary">Abgelaufen</span>
                        @elseif (! $usable)
                            <span class="badge text-bg-warning">Ordner liegt im Papierkorb</span>
                        @endif
                    </span>
                    <div class="d-flex flex-wrap gap-2">
                        @if ($drop->folder)
                            <a href="{{ route('band-manager.cloud.folder', $drop->folder) }}" class="btn btn-sm btn-outline-secondary"><i class="bi bi-folder2-open"></i> Im Speicher</a>
                        @endif
                        <form method="post" action="{{ route('band-manager.photo-drops.destroy', $drop) }}" class="m-0"
                              onsubmit="return confirm('Photo Drop beenden? Der Link funktioniert dann sofort nicht mehr – die Fotos bleiben im Speicher.')">
                            @csrf
                            @method('DELETE')
                            <button class="btn btn-sm btn-outline-danger"><i class="bi bi-x-circle"></i> {{ $usable ? 'Beenden' : 'Entfernen' }}</button>
                        </form>
                    </div>
                </div>

                @if ($usable)
                    <div class="input-group mb-2">
                        <input type="text" class="form-control" value="{{ $url }}" readonly aria-label="Link" data-cl-link-input>
                        <button class="btn btn-primary" type="button" data-cl-copy><i class="bi bi-clipboard"></i> Kopieren</button>
                        <button class="btn btn-outline-primary" type="button" data-pd-share data-title="{{ $drop->folder?->name }} – {{ $band->name }}" hidden>
                            <i class="bi bi-share"></i> Teilen
                        </button>
                        <a href="{{ $url }}" target="_blank" rel="noopener" class="btn btn-outline-secondary" title="Upload-Seite öffnen" aria-label="Upload-Seite öffnen"><i class="bi bi-box-arrow-up-right"></i></a>
                    </div>

                    <div class="d-flex align-items-center gap-3 mb-2">
                        <a href="{{ route('band-manager.photo-drops.qr-code', $drop) }}" target="_blank" rel="noopener" class="pd-qr" title="QR-Code groß anzeigen">
                            <img src="{{ route('band-manager.photo-drops.qr-code', $drop) }}" alt="QR-Code zum Photo Drop „{{ $drop->folder->name }}“" width="88" height="88">
                        </a>
                        <div class="small">
                            <div class="text-muted mb-1">Für Flyer, Leinwand oder Story: scannen und direkt hochladen.</div>
                            <a href="{{ route('band-manager.photo-drops.qr-code', [$drop, 'download' => 1]) }}" class="btn btn-sm btn-outline-secondary">
                                <i class="bi bi-qr-code"></i> QR-Code herunterladen (PNG)
                            </a>
                        </div>
                    </div>
                @endif

                @if ($drop->message)
                    <div class="small fst-italic mb-2"><i class="bi bi-chat-quote"></i> {{ $drop->message }}</div>
                @endif

                <div class="small text-muted d-flex flex-wrap column-gap-3 row-gap-1">
                    <span><i class="bi bi-filetype-jpg"></i> {{ $drop->allow_all_types ? 'alle Dateitypen' : 'nur Fotos & Videos' }}</span>
                    <span><i class="bi bi-hourglass-split"></i> gültig bis {{ $drop->expires_at?->format('d.m.Y') ?? 'unbegrenzt' }}</span>
                    <span><i class="bi bi-clock-history"></i> zuletzt geöffnet: {{ $drop->last_used_at?->format('d.m.Y H:i') ?? 'noch nie' }}</span>
                    <span>erstellt {{ $drop->created_at->format('d.m.Y') }}@if ($drop->creator) von {{ $drop->creator->name }}@endif</span>
                </div>
            </div>
        @empty
            <div class="card card-body shadow-sm text-center text-muted py-4">
                <i class="bi bi-camera fs-3 d-block mb-1"></i>Noch keine Photo Drops – leg oben euren ersten an.
            </div>
        @endforelse
    </div>

    @push('scripts')
        <script>
            document.querySelectorAll('[data-cl-copy]').forEach(button => button.addEventListener('click', async () => {
                const input = button.parentElement.querySelector('[data-cl-link-input]');
                try {
                    await navigator.clipboard.writeText(input.value);
                } catch {
                    input.select();
                    document.execCommand('copy');
                }
                button.innerHTML = '<i class="bi bi-check-lg"></i> Kopiert';
                setTimeout(() => { button.innerHTML = '<i class="bi bi-clipboard"></i> Kopieren'; }, 2000);
            }));

            // Teilen-Menü des Handys (WhatsApp, Instagram …) – nur wo der Browser es kann
            if (navigator.share) {
                document.querySelectorAll('[data-pd-share]').forEach(button => {
                    button.hidden = false;
                    button.addEventListener('click', () => {
                        const url = button.parentElement.querySelector('[data-cl-link-input]').value;
                        navigator.share({ title: button.dataset.title, text: 'Ladet hier eure Fotos hoch:', url }).catch(() => {});
                    });
                });
            }
        </script>
    @endpush
</x-band-manager.layout>
