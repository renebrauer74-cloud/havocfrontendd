@inject('listSections', 'App\Actions\BandManager\Website\ListWebsiteSectionsAction')
@php
    use App\Enums\BandManager\WebsiteFontEnum;
    use App\Enums\MediaCollectionEnum;

    // Nach einem Fehler: Eingaben aus old(), sonst der gespeicherte Stand
    $value = fn (string $field): mixed => old($field, $website->{$field} instanceof \BackedEnum ? $website->{$field}->value : $website->{$field});
    $isOn = fn (string $field): bool => (bool) old($field, $website->{$field});
    $selectedIds = array_map('intval', (array) old('gallery_image_ids', old('_token') ? [] : $selectedImageIds));
    $currentSections = old('sections') ? $listSections->execute(old('sections')) : $sections;
    $publicUrl = route('band-websites.show', $value('slug'));
    $sitesBase = url('sites').'/';
    $thumbUrl = fn ($image): string => ($media = $image->getFirstMedia(MediaCollectionEnum::BandPhotos->value)) ? route('band-manager.website-creator.media', [$band, $media]) : '';
    $profileSpotify = $band->profile->links['spotify'] ?? null;

    // Woher die Inhalte kommen: [Bezeichnung, Icon, Stand, vorhanden?, Recht zum Bearbeiten, Link]
    $sourceItems = [
        ['Biografie', 'person-lines-fill', $sources['bio'] ? 'vorhanden' : 'fehlt', $sources['bio'], 'team.edit', route('band-manager.team.profile.edit', $band)],
        ['Logo', 'badge-ad', $sources['logo'] ? 'vorhanden' : 'fehlt', $sources['logo'], 'team.edit', route('band-manager.team.profile.edit', $band)],
        ['Fotos', 'images', $sources['photos'].' in der Galerie', $sources['photos'] > 0, 'gallery.edit', route('band-manager.gallery.band', $band)],
        ['Bandmitglieder', 'people', (string) $sources['members'], $sources['members'] > 0, 'team.edit', route('band-manager.team.band', $band)],
        ['Links', 'share', $sources['links'].' Plattformen', $sources['links'] > 0, 'team.edit', route('band-manager.team.profile.edit', $band)],
        ['Live-Termine', 'ticket-perforated', $sources['shows'].' bestätigt', $sources['shows'] > 0, 'gigs.view', route('band-manager.gigs.index')],
    ];
    $fontQuery = collect($fonts)->map(fn (WebsiteFontEnum $font): string => $font->googleFontsQuery())->map(fn (string $query): string => explode('&display', $query)[0])->implode('&');
@endphp
<x-band-manager.layout :title="'Website Creator – '.$band->name">
    @push('styles')
        <link href="https://fonts.googleapis.com/css2?{{ $fontQuery }}&display=swap" rel="stylesheet">
        <link href="{{ asset('assets/band-manager/css/website.css') }}?v=3" rel="stylesheet">
    @endpush

    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <div>
            @if ($bands->count() > 1)<a href="{{ route('band-manager.website-creator.index') }}" class="hv-back"><i class="bi bi-arrow-left"></i> Alle Bands</a>@endif
            <h1 class="h3 mb-0 d-flex align-items-center gap-2 flex-wrap">
                {{ $band->name }} – Website
                @if ($website->is_published)
                    <span class="wc-status wc-status--online"><span class="wc-dot"></span> Online</span>
                @else
                    <span class="wc-status">{{ $website->exists ? 'Entwurf' : 'Neu' }}</span>
                @endif
            </h1>
            <div class="small text-muted">
                @if ($website->exists)
                    Stand {{ $website->updated_at->format('d.m.Y, H:i') }} Uhr
                @else
                    Noch nicht gespeichert
                @endif
            </div>
        </div>
        <div class="d-flex flex-wrap align-items-center gap-2">
            @if ($bands->count() > 1)
                <x-band-manager.band-switcher :bands="$bands" :current="$band" route="band-manager.website-creator.band" />
            @endif
            @if ($website->is_published)
                <a href="{{ route('band-websites.show', $website->slug) }}" class="btn btn-outline-secondary" target="_blank" rel="noopener"><i class="bi bi-box-arrow-up-right"></i> Website öffnen</a>
            @endif
        </div>
    </div>

    @if ($canEdit && ! $website->exists)
        <div class="alert alert-info hv-flash">
            <i class="bi bi-magic"></i>
            <span>
                <strong>Willkommen im Website Creator!</strong> Eure Website ist schon fertig befüllt – mit Biografie, Logo, Fotos, Bandmitgliedern,
                Live-Terminen und Links aus dem Band-Manager. Wählt links ein <strong>Theme</strong>, dreht an Farben und Schrift
                und schaut rechts live zu. Unter <strong>„Online“</strong> stellt ihr sie ins Netz.
            </span>
        </div>
    @endif

    <form method="post" action="{{ route('band-manager.website-creator.update', $band) }}" id="wcForm" class="wc-layout" @if ($canEdit) data-wc-preview="{{ route('band-manager.website-creator.preview', $band) }}" @endif>
        @csrf
        @method('PUT')

        {{-- ================= Einstellungen ================= --}}
        <aside class="wc-panel card shadow-sm">
            <ul class="nav wc-tabs" role="tablist">
                @foreach (['design' => ['palette', 'Design'], 'content' => ['collection', 'Inhalte'], 'sections' => ['list-ol', 'Abschnitte'], 'publish' => ['rocket-takeoff', 'Online']] as $tab => [$icon, $label])
                    <li class="nav-item" role="presentation">
                        <button type="button" @class(['nav-link', 'active' => $loop->first]) data-bs-toggle="tab" data-bs-target="#wcTab-{{ $tab }}" role="tab"
                                aria-controls="wcTab-{{ $tab }}" aria-selected="{{ $loop->first ? 'true' : 'false' }}">
                            <i class="bi bi-{{ $icon }}"></i><span>{{ $label }}</span>
                        </button>
                    </li>
                @endforeach
            </ul>

            <fieldset class="wc-panel-body tab-content" @disabled(! $canEdit)>

                {{-- ---------- Design ---------- --}}
                <div class="tab-pane fade show active" id="wcTab-design" role="tabpanel">
                    <div class="wc-label">Theme</div>
                    <p class="wc-hint">Ein Klick setzt Farbe, Schrift und Kopfbereich passend – danach könnt ihr alles einzeln ändern.</p>
                    <div class="wc-themes">
                        @foreach ($themes as $theme)
                            @php($colors = $theme->colors())
                            <label class="wc-theme" style="--t-bg: {{ $colors['bg'] }}; --t-surface: {{ $colors['surface'] }}; --t-text: {{ $colors['text'] }}; --t-accent: {{ $colors['accent'] }}; --t-font: '{{ $theme->defaultFont()->headingFamily() }}'">
                                <input type="radio" name="theme" value="{{ $theme->value }}" @checked($value('theme') === $theme->value)
                                       data-wc-theme data-accent="{{ $colors['accent'] }}" data-font="{{ $theme->defaultFont()->value }}" data-hero="{{ $theme->defaultHeroStyle()->value }}">
                                <span class="wc-theme-preview" aria-hidden="true">
                                    <span class="wc-theme-title">{{ \Illuminate\Support\Str::limit($band->name, 14, '') }}</span>
                                    <span class="wc-theme-bar"></span>
                                    <span class="wc-theme-lines"><i></i><i></i></span>
                                </span>
                                <span class="wc-theme-name">{{ $theme->label() }}</span>
                                <span class="wc-theme-desc">{{ $theme->description() }}</span>
                            </label>
                        @endforeach
                    </div>

                    <div class="wc-label mt-4">Akzentfarbe</div>
                    <div class="wc-colors">
                        <input type="hidden" name="accent_color" value="{{ $value('accent_color') }}" data-wc-accent>
                        <button type="button" class="wc-color wc-color--theme" data-wc-color="" title="Farbe des Themes"><i class="bi bi-arrow-counterclockwise"></i></button>
                        @foreach ($accentPresets as $preset)
                            <button type="button" class="wc-color" style="--c: {{ $preset }}" data-wc-color="{{ $preset }}" title="{{ $preset }}"></button>
                        @endforeach
                        <label class="wc-color wc-color--custom" title="Eigene Farbe">
                            <i class="bi bi-eyedropper"></i>
                            <input type="color" value="{{ $value('accent_color') ?: '#e5383b' }}" data-wc-color-picker aria-label="Eigene Akzentfarbe">
                        </label>
                    </div>

                    <div class="wc-label mt-4">Schrift</div>
                    <div class="wc-fonts">
                        @foreach ($fonts as $font)
                            <label class="wc-font">
                                <input type="radio" name="font" value="{{ $font->value }}" @checked($value('font') === $font->value)>
                                <span class="wc-font-sample" style="font-family: '{{ $font->headingFamily() }}'">{{ \Illuminate\Support\Str::limit($band->name, 12, '') }}</span>
                                <span class="wc-font-name">{{ $font->label() }}</span>
                            </label>
                        @endforeach
                    </div>

                    <div class="wc-label mt-4">Kopfbereich</div>
                    <div class="wc-heroes">
                        @foreach ($heroStyles as $heroStyle)
                            <label class="wc-hero" title="{{ $heroStyle->description() }}">
                                <input type="radio" name="hero_style" value="{{ $heroStyle->value }}" @checked($value('hero_style') === $heroStyle->value)>
                                <span class="wc-hero-icon wc-hero-icon--{{ $heroStyle->value }}" aria-hidden="true"><i></i><b></b></span>
                                <span class="wc-hero-name">{{ $heroStyle->label() }}</span>
                            </label>
                        @endforeach
                    </div>

                    <div class="wc-label mt-4">Effekte</div>
                    @foreach (['effect_grain' => ['Filmkorn', 'Leichtes Rauschen über der ganzen Seite – wie analoges Foto.'], 'effect_animations' => ['Animationen', 'Inhalte gleiten beim Scrollen herein, Laufband, Zoom im Titelfoto.']] as $field => [$label, $hint])
                        <div class="form-check form-switch wc-switch">
                            <input type="hidden" name="{{ $field }}" value="0">
                            <input class="form-check-input" type="checkbox" role="switch" name="{{ $field }}" value="1" id="{{ $field }}" @checked($isOn($field))>
                            <label class="form-check-label" for="{{ $field }}"><strong>{{ $label }}</strong><span>{{ $hint }}</span></label>
                        </div>
                    @endforeach
                </div>

                {{-- ---------- Inhalte ---------- --}}
                <div class="tab-pane fade" id="wcTab-content" role="tabpanel">
                    <div class="wc-label">Kommt automatisch aus dem Band-Manager</div>
                    <div class="wc-sources">
                        @foreach ($sourceItems as [$label, $icon, $state, $ok, $permission, $url])
                            <div @class(['wc-source', 'wc-source--missing' => ! $ok])>
                                <i class="bi bi-{{ $icon }}"></i>
                                <span><strong>{{ $label }}</strong><small>{{ $state }}</small></span>
                                @can($permission)
                                    <a href="{{ $url }}" class="wc-source-edit" title="{{ $label }} bearbeiten"><i class="bi bi-pencil"></i></a>
                                @endcan
                            </div>
                        @endforeach
                    </div>

                    <div class="row g-3 mt-1">
                        <div class="col-sm-5">
                            <label class="form-label" for="language">Sprache der Website</label>
                            <select name="language" id="language" class="form-select">
                                @foreach ($languages as $language)
                                    <option value="{{ $language->value }}" @selected($value('language') === $language->value)>{{ $language->label() }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-sm-7">
                            <label class="form-label" for="tagline">Slogan</label>
                            <input type="text" name="tagline" id="tagline" class="form-control" maxlength="{{ config('band-manager.website.tagline_max') }}"
                                   value="{{ $value('tagline') }}" placeholder="{{ $band->genre ?: 'z. B. Lauter als der Nachbar erlaubt' }}">
                        </div>
                    </div>

                    <div class="mt-3">
                        <label class="form-label" for="about_text">Text „Über uns“</label>
                        <textarea name="about_text" id="about_text" class="form-control" rows="5" maxlength="{{ config('band-manager.team.bio_max') }}" data-md
                                  placeholder="Leer lassen = Biografie aus dem Bandprofil ({{ $sources['bio'] ? 'vorhanden' : 'noch leer' }})">{{ $value('about_text') }}</textarea>
                    </div>

                    <div class="wc-label mt-4">Titelfoto</div>
                    @if ($images->isEmpty())
                        <p class="wc-hint">Noch keine Fotos – ladet in der <a href="{{ route('band-manager.gallery.band', $band) }}" class="hv-link">Galerie</a> welche hoch. Bis dahin zeigt die Website euren Namen ganz groß.</p>
                    @else
                        <div class="wc-photos">
                            <label class="wc-photo wc-photo--auto" title="Hauptbild der Galerie">
                                <input type="radio" name="hero_image_id" value="" @checked(! $value('hero_image_id'))>
                                <span><i class="bi bi-star"></i>Hauptbild</span>
                            </label>
                            @foreach ($images as $image)
                                <label class="wc-photo" title="{{ $image->caption }}">
                                    <input type="radio" name="hero_image_id" value="{{ $image->id }}" @checked((int) $value('hero_image_id') === $image->id)>
                                    <img src="{{ $thumbUrl($image) }}" alt="" loading="lazy">
                                </label>
                            @endforeach
                        </div>

                        <div class="wc-label mt-4">Fotos in der Galerie</div>
                        <div class="wc-photos">
                            @foreach ($images as $image)
                                <label class="wc-photo wc-photo--check" title="{{ $image->caption }}">
                                    <input type="checkbox" name="gallery_image_ids[]" value="{{ $image->id }}" @checked(in_array($image->id, $selectedIds, true))>
                                    <img src="{{ $thumbUrl($image) }}" alt="" loading="lazy">
                                    <i class="bi bi-check-lg"></i>
                                </label>
                            @endforeach
                        </div>
                    @endif

                    <div class="wc-label mt-4">Musik &amp; Video</div>
                    <div class="mb-3">
                        <label class="form-label" for="spotify_url"><i class="bi bi-spotify" style="color:#1DB954"></i> Spotify-Link</label>
                        <input type="url" name="spotify_url" id="spotify_url" class="form-control" value="{{ $value('spotify_url') }}"
                               placeholder="{{ $profileSpotify ?: 'https://open.spotify.com/artist/…' }}">
                        <div class="form-text">Künstler, Album, Song oder Playlist. {{ $profileSpotify ? 'Leer lassen = Link aus dem Bandprofil.' : '' }}</div>
                    </div>
                    <div>
                        <label class="form-label" for="youtube_url"><i class="bi bi-youtube" style="color:#FF0000"></i> YouTube-Video</label>
                        <input type="url" name="youtube_url" id="youtube_url" class="form-control" value="{{ $value('youtube_url') }}" placeholder="https://www.youtube.com/watch?v=…">
                        <div class="form-text">Euer bestes Musikvideo oder ein Live-Mitschnitt.</div>
                    </div>
                    <p class="wc-hint mt-2"><i class="bi bi-shield-check"></i> Spotify und YouTube laden erst, wenn Besucher auf „Inhalt laden“ klicken – datenschutzfreundlich.</p>

                    <div class="wc-label mt-4">Kontakt</div>
                    <label class="form-label" for="contact_email">Kontakt-E-Mail</label>
                    <input type="email" name="contact_email" id="contact_email" class="form-control" value="{{ $value('contact_email') }}" placeholder="{{ $band->contact_email ?: 'band@beispiel.de' }}">
                    <div class="form-check form-switch wc-switch mt-3">
                        <input type="hidden" name="show_agency_booking" value="0">
                        <input class="form-check-input" type="checkbox" role="switch" name="show_agency_booking" value="1" id="show_agency_booking" @checked($isOn('show_agency_booking'))>
                        <label class="form-check-label" for="show_agency_booking"><strong>Booking über die Agentur</strong><span>Zeigt die Booking-Kontaktdaten von {{ config('band-manager.exports.contact.name') }}.</span></label>
                    </div>
                </div>

                {{-- ---------- Abschnitte ---------- --}}
                <div class="tab-pane fade" id="wcTab-sections" role="tabpanel">
                    <p class="wc-hint">Zieht die Abschnitte in die gewünschte Reihenfolge und blendet aus, was ihr nicht braucht. Abschnitte ohne Inhalt erscheinen automatisch nicht.</p>
                    <div class="wc-section wc-section--fixed"><i class="bi bi-pin-angle"></i><span><strong>Kopfbereich</strong><small>immer ganz oben</small></span></div>
                    <ol class="wc-sections" data-wc-sections>
                        @foreach ($currentSections as $entry)
                            @php($section = $entry['section'])
                            <li class="wc-section" draggable="true" data-wc-section>
                                <span class="wc-section-handle" title="Ziehen zum Sortieren"><i class="bi bi-grip-vertical"></i></span>
                                <i class="bi bi-{{ $section->icon() }}"></i>
                                <span><strong>{{ $section->label() }}</strong><small>{{ $section->source() }}</small></span>
                                <span class="wc-section-move">
                                    <button type="button" data-wc-move="-1" aria-label="Nach oben"><i class="bi bi-chevron-up"></i></button>
                                    <button type="button" data-wc-move="1" aria-label="Nach unten"><i class="bi bi-chevron-down"></i></button>
                                </span>
                                <input type="hidden" name="sections[{{ $loop->index }}][key]" value="{{ $section->value }}" data-wc-section-key>
                                <input type="hidden" name="sections[{{ $loop->index }}][enabled]" value="0" data-wc-section-off>
                                <span class="form-check form-switch m-0">
                                    <input class="form-check-input" type="checkbox" role="switch" name="sections[{{ $loop->index }}][enabled]" value="1" @checked($entry['enabled'])
                                           aria-label="{{ $section->label() }} anzeigen" data-wc-section-on>
                                </span>
                            </li>
                        @endforeach
                    </ol>
                </div>

                {{-- ---------- Online ---------- --}}
                <div class="tab-pane fade" id="wcTab-publish" role="tabpanel">
                    <label class="form-label" for="slug">Adresse eurer Website</label>
                    <div class="input-group">
                        <span class="input-group-text wc-url-prefix">{{ preg_replace('#^https?://#', '', $sitesBase) }}</span>
                        <input type="text" name="slug" id="slug" class="form-control" maxlength="{{ config('band-manager.website.slug_max') }}" value="{{ $value('slug') }}"
                               pattern="[a-z0-9]+(-[a-z0-9]+)*" required data-wc-slug>
                    </div>
                    <div class="form-text">Nur Kleinbuchstaben, Zahlen und Bindestriche.</div>

                    <div @class(['wc-publish', 'is-online' => $isOn('is_published')]) data-wc-publish>
                        <div class="form-check form-switch m-0">
                            <input type="hidden" name="is_published" value="0">
                            <input class="form-check-input" type="checkbox" role="switch" name="is_published" value="1" id="is_published" @checked($isOn('is_published'))>
                            <label class="form-check-label" for="is_published"><strong>Website ist online</strong></label>
                        </div>
                        <p class="m-0 small">Online ist eure Website für alle unter dieser Adresse erreichbar und kann von Google gefunden werden. Offline sieht sie nur ihr hier.</p>
                    </div>

                    {{-- Eigene Domain: Ziel für die Weiterleitung beim Domain-Anbieter --}}
                    <div class="wc-domain" data-wc-domain>
                        <div class="wc-label mb-1"><i class="bi bi-signpost-split"></i> Eigene Domain verbinden</div>
                        <p class="small mb-2">Ihr habt schon eine Domain (z. B. <em>www.{{ \Illuminate\Support\Str::slug($band->name) ?: 'eureband' }}.de</em>)? Leitet sie auf diese Adresse weiter:</p>
                        <div class="wc-share">
                            <a href="{{ $publicUrl }}" target="_blank" rel="noopener" class="wc-share-url" data-wc-target>{{ $publicUrl }}</a>
                            <button type="button" class="btn btn-sm btn-outline-secondary" data-wc-copy><i class="bi bi-clipboard"></i> Kopieren</button>
                        </div>
                        <ol class="wc-domain-steps">
                            <li>Beim Domain-Anbieter einloggen (z. B. IONOS, Strato, All-Inkl, GoDaddy).</li>
                            <li>Bei eurer Domain <strong>„Weiterleitung“</strong> bzw. <strong>„Umleitung (HTTP 301)“</strong> wählen.</li>
                            <li>Als Ziel die Adresse oben eintragen und speichern – nach wenigen Minuten (spätestens 24 Stunden) landen Besucher auf eurer Band-Website.</li>
                        </ol>
                        <p class="wc-domain-note small mb-0" data-wc-domain-offline @if ($isOn('is_published')) hidden @endif>
                            <i class="bi bi-exclamation-triangle"></i> Die Weiterleitung funktioniert erst, wenn eure Website <strong>online</strong> ist.
                        </p>
                        <p class="wc-domain-note small mb-0" data-wc-domain-changed hidden>
                            <i class="bi bi-exclamation-triangle"></i> Ihr habt die Adresse geändert – bitte speichern und die Weiterleitung beim Domain-Anbieter auf die neue Adresse anpassen.
                        </p>
                    </div>

                    <label class="form-label mt-4" for="imprint">Impressum</label>
                    <textarea name="imprint" id="imprint" class="form-control" rows="6" maxlength="{{ config('band-manager.website.imprint_max') }}" data-md
                              placeholder="Angaben gemäß § 5 DDG&#10;Vorname Nachname&#10;Straße Nr.&#10;PLZ Ort&#10;E-Mail: …">{{ $value('imprint') }}</textarea>
                    <div class="form-text"><i class="bi bi-exclamation-triangle"></i> Websites aus Deutschland brauchen ein Impressum mit Name, Anschrift und E-Mail. Es erscheint unten auf der Website.</div>
                </div>
            </fieldset>

            @if ($canEdit)
                <div class="wc-panel-foot">
                    <span class="wc-dirty" data-wc-dirty hidden><i class="bi bi-circle-fill"></i> Ungespeicherte Änderungen</span>
                    <button class="btn btn-primary ms-auto"><i class="bi bi-check2"></i> Speichern</button>
                </div>
            @endif
        </aside>

        {{-- ================= Live-Vorschau ================= --}}
        <section class="wc-stage" aria-label="Vorschau">
            <div class="wc-stage-bar">
                <div class="wc-devices" role="group" aria-label="Gerät">
                    @foreach (['desktop' => ['display', 'Desktop'], 'tablet' => ['tablet', 'Tablet'], 'mobile' => ['phone', 'Handy']] as $device => [$icon, $label])
                        <button type="button" @class(['active' => $loop->first]) data-wc-device="{{ $device }}" title="{{ $label }}" aria-label="{{ $label }}"><i class="bi bi-{{ $icon }}"></i></button>
                    @endforeach
                </div>
                <div class="wc-stage-url"><i class="bi bi-lock-fill"></i> <span data-wc-url>{{ preg_replace('#^https?://#', '', $publicUrl) }}</span></div>
                <span class="wc-stage-state" data-wc-state><i class="bi bi-check-circle"></i> Live</span>
                <a href="{{ route('band-manager.website-creator.show', $band) }}" target="_blank" rel="noopener" class="wc-stage-open" title="Gespeicherten Stand in neuem Tab öffnen"><i class="bi bi-arrows-fullscreen"></i></a>
            </div>
            <div class="wc-stage-viewport">
                <div class="wc-frame" data-wc-frame-wrap>
                    <iframe src="{{ route('band-manager.website-creator.show', $band) }}" title="Vorschau der Website" data-wc-frame></iframe>
                </div>
            </div>
        </section>
    </form>

    @push('scripts')
        <script src="{{ asset('assets/band-manager/js/website-creator.js') }}?v=2"></script>
    @endpush
</x-band-manager.layout>
