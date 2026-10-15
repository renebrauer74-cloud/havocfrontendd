<x-band-manager.layout :title="'Kalender – '.$band->name">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <div>
            @if (auth()->user()->isAdmin())
                <a href="{{ route('band-manager.calendar.index') }}" class="small">&larr; Alle Bands</a>
            @endif
            <h1 class="h3 mb-0">{{ $band->name }} – Verfügbarkeiten</h1>
        </div>
        <div class="d-flex flex-wrap gap-3 small">
            @foreach ($statuses as $status)
                <span><span class="status-dot" style="background:{{ $status['color'] }}"></span>{{ $status['label'] }}</span>
            @endforeach
        </div>
    </div>

    @if ($canEdit)
        <p class="text-muted small">Klicke auf einen Tag oder ziehe über mehrere Tage (mobil: lange tippen), um den Status zu setzen.</p>
    @else
        <p class="text-muted small">Nur Leseansicht.</p>
    @endif

    @if ($unreadMessages)
        <div class="alert alert-warning d-flex justify-content-between align-items-center">
            <span>✉️ Du hast {{ $unreadMessages }} ungelesene {{ $unreadMessages > 1 ? 'Nachrichten' : 'Nachricht' }} von der Agentur.</span>
            <a href="{{ route('band-manager.messages.index') }}" class="btn btn-sm btn-dark">Lesen</a>
        </div>
    @endif

    <div class="card card-body shadow-sm"><div id="calendar"></div></div>

    @if ($canEdit)
        <div class="modal fade" id="statusModal" tabindex="-1">
            <div class="modal-dialog">
                <form class="modal-content" id="statusForm">
                    <div class="modal-header">
                        <h5 class="modal-title">Status setzen</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Schließen"></button>
                    </div>
                    <div class="modal-body">
                        <p class="fw-semibold" id="statusRange"></p>
                        <div class="d-grid gap-2 mb-3">
                            @foreach ($statuses as $key => $status)
                                <input type="radio" class="btn-check" name="status" id="st-{{ $key }}" value="{{ $key }}" required>
                                <label class="btn btn-outline-secondary text-start" for="st-{{ $key }}">
                                    <span class="status-dot" style="background:{{ $status['color'] }}"></span>{{ $status['label'] }}
                                </label>
                            @endforeach
                        </div>
                        <label class="form-label" for="statusNote">Notiz (optional)</label>
                        <textarea name="note" id="statusNote" class="form-control" rows="2" maxlength="500"
                                  placeholder="z.B. nur abends möglich, Anreise ab 14 Uhr …"></textarea>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-danger me-auto" id="btnClear">Eintrag löschen</button>
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Abbrechen</button>
                        <button type="submit" class="btn btn-primary">Speichern</button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    @push('scripts')
        @php
            $calendarConfig = [
                'api' => route('band-manager.api.availability.index', $band),
                'csrf' => csrf_token(),
                'canEdit' => $canEdit,
            ];
        @endphp
        <script>
            window.CALENDAR_CONFIG = @json($calendarConfig);
        </script>
        <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.15/index.global.min.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/@fullcalendar/core@6.1.15/locales-all.global.min.js"></script>
        <script src="{{ asset('assets/band-manager/js/calendar.js') }}?v=2"></script>
    @endpush
</x-band-manager.layout>
