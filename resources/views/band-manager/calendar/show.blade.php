<x-band-manager.layout :title="'Kalender – '.$band->name">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <div>
            @if (auth()->user()->isAdmin())
                <a href="{{ route('band-manager.calendar.index') }}" class="hv-back"><i class="bi bi-arrow-left"></i> Alle Bands</a>
            @endif
            <h1 class="h3 mb-0">{{ $band->name }} – Verfügbarkeiten</h1>
        </div>
        <div class="d-flex flex-wrap gap-3 small">
            @foreach ($statuses as $status)
                <span><span class="status-dot" style="background:{{ $status->color() }}"></span>{{ $status->label() }}</span>
            @endforeach
        </div>
    </div>

    @if ($canEdit)
        <div class="hv-cal-hint">
            <i class="bi bi-hand-index-thumb"></i>
            <span class="hv-cal-hint-touch"><strong>Tippe auf einen Tag</strong>, um den Status, eine Probe oder einen anderen Termin einzutragen. Für mehrere Tage: Tag gedrückt halten und weiterziehen.</span>
            <span class="hv-cal-hint-mouse"><strong>Klicke auf einen Tag</strong> oder ziehe über mehrere Tage, um den Status, eine Probe oder einen anderen Termin einzutragen.</span>
        </div>
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
                        <h5 class="modal-title">Eintrag im Kalender</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Schließen"></button>
                    </div>
                    <div class="modal-body">
                        <p class="fw-semibold" id="statusRange"></p>
                        <div class="d-grid gap-2 mb-3">
                            @foreach ($statuses as $status)
                                <input type="radio" class="btn-check" name="status" id="st-{{ $status->value }}" value="{{ $status->value }}" required
                                       @if ($status->requiresTitle()) data-requires-title @endif>
                                <label class="btn btn-outline-secondary text-start" for="st-{{ $status->value }}">
                                    <span class="status-dot" style="background:{{ $status->color() }}"></span>{{ $status->label() }}
                                </label>
                            @endforeach
                        </div>

                        <div class="mb-3">
                            <label class="form-label" for="statusTitle">Titel <span id="statusTitleHint">(optional)</span></label>
                            <input type="text" name="title" id="statusTitle" class="form-control" maxlength="100"
                                   placeholder="z. B. Musikvideo-Dreh, Fotoshooting, Probe im Proberaum …">
                        </div>

                        <div class="form-check form-switch mb-2">
                            <input class="form-check-input" type="checkbox" name="all_day" id="statusAllDay" value="1" checked>
                            <label class="form-check-label" for="statusAllDay">Ganztägig</label>
                        </div>
                        <div class="row g-2 mb-3 d-none" id="statusTimes">
                            <div class="col-6">
                                <label class="form-label" for="statusStart">Von</label>
                                <input type="time" name="start_time" id="statusStart" class="form-control">
                            </div>
                            <div class="col-6">
                                <label class="form-label" for="statusEnd">Bis (optional)</label>
                                <input type="time" name="end_time" id="statusEnd" class="form-control">
                            </div>
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
        <script src="{{ asset('assets/band-manager/js/calendar.js') }}?v=4"></script>
    @endpush
</x-band-manager.layout>
