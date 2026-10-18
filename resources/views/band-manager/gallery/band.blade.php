@php
    $count = $images->count();
    $free = max(0, $max - $count);
    $maxMb = (int) floor($maxBytes / 1048576);
@endphp
<x-band-manager.layout :title="'Galerie – '.$band->name">
    @push('styles')
        <link href="{{ asset('assets/band-manager/css/gallery.css') }}" rel="stylesheet">
    @endpush

    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <div>
            @if ($bands->count() > 1)<a href="{{ route('band-manager.gallery.index') }}" class="small">&larr; Alle Bands</a>@endif
            <h1 class="h3 mb-0">{{ $band->name }} – Galerie</h1>
        </div>
        <div class="d-flex flex-wrap align-items-center gap-2">
            <span class="badge text-bg-light border fs-6 fw-normal">{{ $count }} / {{ $max }} Bilder</span>
            @if ($bands->count() > 1)
                <x-band-manager.band-switcher :bands="$bands" :current="$band" route="band-manager.gallery.band" />
            @endif
        </div>
    </div>

    @unless ($hasMain)
        <div class="alert alert-warning hv-flash">
            <i class="bi bi-star"></i>
            <span>
                <strong>Wichtig: Es ist noch kein Hauptbild festgelegt.</strong><br>
                Bitte lade ein aktuelles <strong>Bandfoto</strong> hoch und markiere es als <strong>„Hauptbild“</strong>.
                Das Hauptbild ist euer offizielles Bandbild – es erscheint z. B. auf dem Titelblatt des Pressekits.
            </span>
        </div>
    @else
        <p class="text-muted small">
            <i class="bi bi-star-fill text-primary"></i> Das <strong>Hauptbild</strong> ist das offizielle Bandfoto und erscheint auf dem Titelblatt des Pressekits.
            Die weiteren Plätze sind z. B. für Live-Fotos von Gigs oder Standbilder aus Musikvideos.
        </p>
    @endunless

    @if ($canEdit)
        @if ($free > 0)
            <form method="post" action="{{ route('band-manager.gallery.store', $band) }}" enctype="multipart/form-data" class="card card-body shadow-sm mb-4">
                @csrf
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label" for="glInput">Bilder * <small class="text-muted">(noch {{ $free }} frei · JPG, PNG, WebP · max. {{ $maxMb }} MB pro Bild)</small></label>
                        <input type="file" name="photos[]" id="glInput" class="form-control" accept="image/jpeg,image/png,image/webp" multiple required
                               data-free="{{ $free }}" data-max-bytes="{{ $maxBytes }}" data-post-max="{{ $postMax }}">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="category">Art</label>
                        <select name="category" id="category" class="form-select">
                            @foreach ($categories as $category)
                                <option value="{{ $category->value }}">{{ $category->label() }}</option>
                            @endforeach
                        </select>
                    </div>
                    <x-band-manager.field name="caption" label="Bildunterschrift (optional)" placeholder="z. B. Live im Gloria, Köln 2025" :max="190" />
                    <x-band-manager.field name="credit" label="Fotograf / © (optional)" placeholder="z. B. Max Mustermann" />
                    <div class="col-12">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="make_main" value="1" id="make_main" @checked(! $hasMain)>
                            <label class="form-check-label" for="make_main">
                                <strong>Als Hauptbild festlegen</strong>
                                <span class="text-muted small">(gilt für das erste ausgewählte Bild{{ $hasMain ? ' – ersetzt das bisherige Hauptbild' : '' }})</span>
                            </label>
                        </div>
                    </div>
                </div>
                <div class="mt-3"><button class="btn btn-primary"><i class="bi bi-upload"></i> Hochladen</button></div>
            </form>
        @else
            <div class="alert alert-info">Alle {{ $max }} Plätze sind belegt. Lösche ein Bild, um ein neues hochzuladen.</div>
        @endif
    @endif

    <div class="gl-grid">
        @foreach ($images as $image)
            @php($photo = $image->getFirstMedia(\App\Enums\MediaCollectionEnum::BandPhotos->value))
            <div @class(['gl-card', 'gl-card--main' => $image->is_main])>
                <a href="{{ route('band-manager.gallery.image', ['image' => $image, 'size' => 'original']) }}" target="_blank" rel="noopener" class="gl-thumb" title="In Originalgröße öffnen">
                    <img src="{{ route('band-manager.gallery.image', $image) }}" alt="{{ $image->caption }}" loading="lazy">
                    @if ($image->is_main)<span class="gl-main-badge"><i class="bi bi-star-fill"></i> Hauptbild</span>@endif
                </a>
                <div class="gl-body">
                    <span class="gl-cat">{{ $image->category->label() }}</span>
                    @if ($image->caption)<div class="gl-caption">{{ $image->caption }}</div>@endif
                    <div class="gl-meta">
                        @if ($image->credit)© {{ $image->credit }} · @endif
                        @if ($photo?->getCustomProperty('width')){{ $photo->getCustomProperty('width') }} × {{ $photo->getCustomProperty('height') }} px · @endif
                        {{ $photo?->human_readable_size }}
                    </div>
                </div>
                <div class="gl-actions">
                    @if ($canEdit && ! $image->is_main)
                        <form method="post" action="{{ route('band-manager.gallery.main', $image) }}" class="m-0 flex-grow-1">
                            @csrf
                            <button class="btn btn-sm btn-outline-primary w-100"><i class="bi bi-star"></i> Als Hauptbild</button>
                        </form>
                    @endif
                    <a href="{{ route('band-manager.gallery.image', ['image' => $image, 'download' => 1]) }}" class="btn btn-sm btn-outline-secondary"
                       title="Original herunterladen" aria-label="Original herunterladen"><i class="bi bi-download"></i></a>
                    @if ($canEdit)
                        <button class="btn btn-sm btn-outline-secondary" type="button" data-bs-toggle="collapse" data-bs-target="#glEdit{{ $image->id }}"
                                title="Bearbeiten" aria-label="Bearbeiten"><i class="bi bi-pencil"></i></button>
                        <form method="post" action="{{ route('band-manager.gallery.destroy', $image) }}" class="m-0"
                              onsubmit="return confirm(@js($image->is_main ? 'Das ist das HAUPTBILD! Wirklich löschen?' : 'Bild wirklich löschen?'))">
                            @csrf
                            @method('DELETE')
                            <button class="btn btn-sm btn-outline-danger" title="Löschen" aria-label="Löschen"><i class="bi bi-trash3"></i></button>
                        </form>
                    @endif
                </div>
                @if ($canEdit)
                    <div class="collapse" id="glEdit{{ $image->id }}">
                        <form method="post" action="{{ route('band-manager.gallery.update', $image) }}" class="gl-edit">
                            @csrf
                            @method('PUT')
                            <input name="caption" class="form-control form-control-sm mb-2" maxlength="190" placeholder="Bildunterschrift" value="{{ $image->caption }}" aria-label="Bildunterschrift">
                            <input name="credit" class="form-control form-control-sm mb-2" maxlength="150" placeholder="Fotograf / ©" value="{{ $image->credit }}" aria-label="Fotograf">
                            @if ($image->is_main)
                                <div class="form-text mt-0 mb-2">Das Hauptbild ist immer ein Bandfoto.</div>
                            @else
                                <select name="category" class="form-select form-select-sm mb-2" aria-label="Art">
                                    @foreach ($categories as $category)
                                        <option value="{{ $category->value }}" @selected($image->category === $category)>{{ $category->label() }}</option>
                                    @endforeach
                                </select>
                            @endif
                            <button class="btn btn-sm btn-primary w-100">Speichern</button>
                        </form>
                    </div>
                @endif
            </div>
        @endforeach

        @for ($slot = 0; $slot < $free; $slot++)
            <div class="gl-slot"><i class="bi bi-plus-lg"></i><span>Freier Platz</span></div>
        @endfor
    </div>

    @push('scripts')
        <script>
            // Vor dem Hochladen prüfen: Anzahl, Größe pro Bild, Gesamtgröße (sonst lehnt der Server alles ab)
            document.getElementById('glInput')?.addEventListener('change', e => {
                const input = e.target;
                const free = +input.dataset.free;
                const maxB = +input.dataset.maxBytes;
                const postMax = +input.dataset.postMax;
                const files = [...input.files];
                const mb = b => (b / 1048576).toFixed(0) + ' MB';
                const msgs = [];

                if (files.length > free) msgs.push(`Es sind nur noch ${free} Plätze frei – bitte höchstens ${free} Bilder auswählen.`);
                const big = files.filter(f => f.size > maxB).map(f => f.name);
                if (big.length) msgs.push(`Zu groß (max. ${mb(maxB)} pro Bild): ${big.join(', ')}`);
                const total = files.reduce((s, f) => s + f.size, 0);
                if (postMax > 0 && total > postMax * 0.95) msgs.push(`Zusammen zu groß für den Server (max. ${mb(postMax)} pro Upload) – bitte weniger Bilder auf einmal auswählen.`);

                if (msgs.length) { alert(msgs.join('\n\n')); input.value = ''; }
            });
        </script>
    @endpush
</x-band-manager.layout>
