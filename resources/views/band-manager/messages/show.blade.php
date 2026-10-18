@php
    $me = auth()->user();
    $confirmDelete = $seesAll
        ? 'Unterhaltung mit allen Nachrichten für ALLE Beteiligten löschen? Das kann nicht rückgängig gemacht werden.'
        : 'Unterhaltung aus deiner Liste entfernen? Die Agentur behält ihre Kopie.';
@endphp
<x-band-manager.layout :title="$thread->subject">
    <a href="{{ route('band-manager.messages.index') }}" class="hv-back"><i class="bi bi-arrow-left"></i> Alle Nachrichten</a>

    <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-4">
        <div>
            <h1 class="h3 mb-1">{{ $thread->subject }}</h1>
            <p class="text-muted small mb-0">Band: <strong>{{ $thread->band->name }}</strong></p>
        </div>
        <form method="post" action="{{ route('band-manager.messages.destroy', $thread) }}" class="m-0"
              onsubmit="return confirm(@js($confirmDelete))">
            @csrf
            @method('DELETE')
            <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash3"></i> Unterhaltung löschen</button>
        </form>
    </div>

    <div class="d-flex flex-column gap-3 mb-4" style="max-width:760px">
        @foreach ($messages as $message)
            @php
                $own = $message->isFrom($me);
                $agency = (bool) $message->user?->isAdmin();
            @endphp
            <div @class(['card', 'ms-auto border-dark' => $own, 'me-auto' => ! $own])
                 style="max-width:85%;{{ $agency ? 'background:var(--hv-surface)' : '' }}">
                <div class="card-body py-2">
                    <div class="d-flex justify-content-between align-items-start gap-3 small text-muted mb-1">
                        <span>
                            <strong>{{ $message->user?->name ?? 'Gelöschter Benutzer' }}</strong>
                            @if ($agency) · Agentur @endif
                            · {{ $message->created_at->format('d.m.Y H:i') }}
                        </span>
                        @if ($own || $seesAll)
                            <form method="post" action="{{ route('band-manager.messages.messages.destroy', [$thread, $message]) }}" class="m-0"
                                  onsubmit="return confirm('Diese Nachricht wirklich löschen?')">
                                @csrf
                                @method('DELETE')
                                <button class="btn btn-sm btn-outline-danger border-0 py-0 px-1"
                                        title="Nachricht löschen" aria-label="Nachricht löschen"><i class="bi bi-trash3"></i></button>
                            </form>
                        @endif
                    </div>
                    <div>{!! md($message->body) !!}</div>
                </div>
            </div>
        @endforeach
    </div>

    <form id="antwort" method="post" action="{{ route('band-manager.messages.reply', $thread) }}"
          class="card card-body shadow-sm" style="max-width:760px">
        @csrf
        <div class="row">
            <x-band-manager.textarea name="body" label="Antwort" :rows="4" :max="5000" required />
        </div>
        <div class="mt-3"><button class="btn btn-primary">Antworten</button></div>
    </form>
</x-band-manager.layout>
