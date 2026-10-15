@php
    $count = fn (int $number, string $one, string $many): string => $number === 1 ? "1 {$one}" : "{$number} {$many}";
@endphp
<x-band-manager.layout title="Pressekit zusammenstellen">
    <a href="{{ route('band-manager.exports.index') }}" class="small">&larr; Export</a>
    <h1 class="h3 mb-1">Pressekit zusammenstellen</h1>
    <p class="text-muted small mb-4" style="max-width:820px">
        Hake an, was ins Pressekit soll. Du bekommst eine <strong>ZIP-Datei</strong> mit einem Ordner: PDFs (Bandinfo, Fotos, Live-Termine),
        dazu Rider und Fotos in Originalgröße. Gagen, interne Notizen, Anfragen und private Daten (Adresse, Telefon, Geburtsdatum …)
        kommen <strong>nie</strong> ins Pressekit.
    </p>

    @foreach ($missing as $message)
        <div class="alert alert-danger hv-flash"><i class="bi bi-exclamation-octagon"></i><span>{{ $message }}</span></div>
    @endforeach

    @if ($bands->count() > 1)
        <form method="get" action="{{ route('band-manager.presskit.create') }}" class="mb-4" style="max-width:420px">
            <label class="form-label" for="band">Band</label>
            <select name="band" id="band" class="form-select" onchange="this.form.submit()">
                <option value="">– bitte wählen –</option>
                @foreach ($bands as $option)
                    <option value="{{ $option->id }}" @selected($band?->is($option))>{{ $option->name }}</option>
                @endforeach
            </select>
        </form>
    @endif

    @if ($bands->isEmpty())
        <div class="alert alert-info">Dir ist noch keine Band zugeordnet. Bitte wende dich an die Agentur.</div>
    @elseif (! $band)
        <div class="alert alert-info">Bitte zuerst eine Band wählen.</div>
    @else
        @unless ($inventory['main'])
            <div class="alert alert-warning hv-flash"><i class="bi bi-star"></i><span>
                <strong>Kein Hauptbild festgelegt</strong> – ohne Hauptbild hat die Bandinfo kein Titelbild.
                @can('gallery.view')<a href="{{ route('band-manager.gallery.band', $band) }}" class="alert-link">Jetzt in der Galerie festlegen</a>@endcan</span></div>
        @endunless
        @if (! $inventory['bio_de'] && ! $inventory['bio_en'])
            <div class="alert alert-warning hv-flash"><i class="bi bi-journal-text"></i><span>
                <strong>Noch keine Biografie</strong> – sie ist das Herzstück des Pressekits.
                @can('team.edit')<a href="{{ route('band-manager.team.profile.edit', $band) }}" class="alert-link">Jetzt schreiben</a>@endcan</span></div>
        @endif

        <form method="post" action="{{ route('band-manager.presskit.store') }}" id="pkForm">
            @csrf
            <input type="hidden" name="band_id" value="{{ $band->id }}">

            <div class="row g-4">
                <div class="col-lg-8">

                    {{-- Bandinfo --}}
                    <div class="card shadow-sm mb-4">
                        <div class="card-header"><i class="bi bi-file-earmark-person me-1"></i> Bandinfo (PDF)</div>
                        <div class="card-body">
                            <div class="form-label">Sprache</div>
                            <div class="d-flex flex-wrap gap-4">
                                <x-band-manager.presskit-option group="lang" value="de" label="🇩🇪 Deutsch" :hint="$inventory['bio_de'] ? 'Biografie vorhanden' : 'ohne Biografie'" />
                                <x-band-manager.presskit-option group="lang" value="en" label="🇬🇧 English" :hint="$inventory['bio_en'] ? 'Biografie vorhanden' : 'ohne Biografie'" :checked="$inventory['bio_en']" />
                            </div>
                            <div class="form-text mt-0 mb-3">Pro Sprache entsteht eine eigene Bandinfo-PDF.</div>

                            <div class="form-label">Inhalt</div>
                            <x-band-manager.presskit-option group="inc" value="cover" label="Hauptbild als Titelbild"
                                                            :hint="$inventory['main'] ? '' : 'noch kein Hauptbild festgelegt'" :enabled="$inventory['main']" />
                            <x-band-manager.presskit-option group="inc" value="bio" label="Biografie" :enabled="$inventory['bio_de'] || $inventory['bio_en']" />
                            <x-band-manager.presskit-option group="inc" value="members" label="Besetzung (Bandmitglieder)"
                                                            :hint="$inventory['members'] ? $count($inventory['members'], 'Person', 'Personen').' – nur Name, Instrument, Foto' : 'keine angelegt'"
                                                            :enabled="$inventory['members'] > 0" />
                            <div class="ms-4">
                                <x-band-manager.presskit-option group="inc" value="member_bios" label="… mit Steckbriefen der Mitglieder" :checked="false" :enabled="$inventory['members'] > 0" />
                            </div>
                            <x-band-manager.presskit-option group="inc" value="links" label="Social Media & Streaming-Links"
                                                            :hint="$inventory['links'] ? $count($inventory['links'], 'Link', 'Links') : 'keine hinterlegt'" :enabled="$inventory['links'] > 0" />
                            <x-band-manager.presskit-option group="inc" value="contact" label="Kontakt & Booking" :hint="$inventory['contact']" />
                        </div>
                    </div>

                    {{-- Fotos --}}
                    <div class="card shadow-sm mb-4">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <span><i class="bi bi-images me-1"></i> Fotos</span>
                            @can('gallery.view')<a href="{{ route('band-manager.gallery.band', $band) }}" class="small">Galerie öffnen</a>@endcan
                        </div>
                        <div class="card-body">
                            <x-band-manager.presskit-option group="inc" value="photos_pdf" label="Foto-Übersicht (PDF)"
                                                            :hint="$count($inventory['images'], 'Bild', 'Bilder')" :enabled="$inventory['images'] > 0" />
                            <x-band-manager.presskit-option group="inc" value="photos_files" label="Fotos in Originalgröße (Ordner „Fotos“)"
                                                            hint="für Presse & Veranstalter, inkl. Bildnachweisen" :enabled="$inventory['images'] > 0" />
                        </div>
                    </div>

                    {{-- Rider --}}
                    <div class="card shadow-sm mb-4">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <span><i class="bi bi-file-earmark-pdf me-1"></i> Rider</span>
                            @can('documents.view')<a href="{{ route('band-manager.documents.index', ['band' => $band]) }}" class="small">Dokumente öffnen</a>@endcan
                        </div>
                        <div class="card-body">
                            @forelse ($inventory['riders'] as $rider)
                                <x-band-manager.presskit-option group="riders" :value="(string) $rider->id" :label="$rider->title"
                                                                :hint="$rider->categoryLabel().' · '.$rider->sizeLabel()" />
                            @empty
                                <span class="text-muted small">Noch keine Rider hochgeladen.</span>
                            @endforelse
                        </div>
                    </div>

                    {{-- Live-Termine --}}
                    <div class="card shadow-sm mb-4">
                        <div class="card-header"><i class="bi bi-music-note-beamed me-1"></i> Live-Termine (PDF)</div>
                        <div class="card-body">
                            <x-band-manager.presskit-option group="inc" value="gigs_upcoming" label="Kommende Shows"
                                                            :hint="$count($inventory['upcoming'], 'bestätigter Termin', 'bestätigte Termine')" :enabled="$inventory['upcoming'] > 0" />
                            <x-band-manager.presskit-option group="inc" value="gigs_past" :label="'Vergangene Shows (letzte '.$inventory['pastMonths'].' Monate)'"
                                                            :hint="$count($inventory['past'], 'Termin', 'Termine')" :checked="false" :enabled="$inventory['past'] > 0" />
                            <div class="form-text">Nur bestätigte Gigs – Anfragen, abgesagte Termine und Gagen erscheinen nicht.</div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-4">
                    <div class="card shadow-sm" style="position:sticky; top:1.5rem">
                        <div class="card-header">Download</div>
                        <div class="card-body">
                            <p class="small text-muted mb-2">So sieht der Ordner im ZIP aus:</p>
<pre class="small mb-3" style="white-space:pre-wrap">{{ $rootName }}/
├ 01_Bandinfo_DE.pdf
├ 01_Bandinfo_EN.pdf
├ 02_Fotos.pdf
├ 03_Live-Termine.pdf
├ Fotos/
└ Rider/</pre>
                            <button class="btn btn-primary w-100" id="pkSubmit" @disabled($missing)>
                                <i class="bi bi-file-earmark-zip"></i> Pressekit herunterladen
                            </button>
                            <div class="form-text">Die Erstellung dauert je nach Anzahl der Bilder ein paar Sekunden.</div>
                        </div>
                    </div>
                </div>
            </div>
        </form>

        @push('scripts')
            <script>
                // Während der Erstellung Button sperren (Download öffnet keine neue Seite → nach kurzer Zeit wieder frei)
                document.getElementById('pkForm')?.addEventListener('submit', () => {
                    const btn = document.getElementById('pkSubmit');
                    const html = btn.innerHTML;
                    setTimeout(() => { btn.disabled = true; btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Wird erstellt …'; }, 0);
                    setTimeout(() => { btn.disabled = false; btn.innerHTML = html; }, 8000);
                });
            </script>
        @endpush
    @endif
</x-band-manager.layout>
