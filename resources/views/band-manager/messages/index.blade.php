<x-band-manager.layout title="Nachrichten">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <h1 class="h3 mb-0">Nachrichten</h1>
        <div class="d-flex flex-wrap gap-2">
            @if ($bands->count() > 1)
                <form method="get" action="{{ route('band-manager.messages.index') }}">
                    <select name="band" class="form-select w-auto" onchange="this.form.submit()" aria-label="Band">
                        <option value="">Alle Bands</option>
                        @foreach ($bands as $band)
                            <option value="{{ $band->id }}" @selected($bandId === $band->id)>{{ $band->name }}</option>
                        @endforeach
                    </select>
                </form>
            @endif
            @if ($bands->isNotEmpty())
                <a href="{{ route('band-manager.messages.create', array_filter(['band' => $bandId])) }}" class="btn btn-primary">+ Neue Nachricht</a>
            @endif
        </div>
    </div>

    @if ($bands->isEmpty())
        <div class="alert alert-info">Dir ist noch keine Band zugeordnet. Bitte wende dich an die Agentur.</div>
    @else
        <div class="card shadow-sm">
            <div class="list-group list-group-flush">
                @forelse ($threads as $thread)
                    <a href="{{ route('band-manager.messages.show', $thread) }}"
                       class="list-group-item list-group-item-action d-flex justify-content-between align-items-start gap-3">
                        <div style="min-width:0">
                            <div @class(['fw-bold' => $thread->unread])>
                                @if ($thread->unread)<span class="badge bg-primary me-1">Neu</span>@endif
                                {{ $thread->subject }}
                            </div>
                            <div class="small text-muted text-truncate">
                                @if ($bands->count() > 1)<strong>{{ $thread->band->name }}</strong> · @endif
                                {{ mb_strimwidth(md_plain((string) $thread->last_body), 0, 120, '…') }}
                            </div>
                        </div>
                        <small class="text-muted text-nowrap">{{ $thread->last_message_at->format('d.m.Y H:i') }}</small>
                    </a>
                @empty
                    <div class="list-group-item text-muted">Noch keine Nachrichten.</div>
                @endforelse
            </div>
        </div>
    @endif
</x-band-manager.layout>
