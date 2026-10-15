<div class="col-lg-6">
    <div class="card shadow-sm h-100">
        <div class="card-header d-flex justify-content-between align-items-center">
            <span>Nachrichten</span><a href="{{ route('band-manager.messages.index') }}" class="small">Alle</a>
        </div>
        <ul class="list-group list-group-flush">
            @forelse ($latestThreads as $thread)
                <li class="list-group-item d-flex justify-content-between align-items-center gap-2">
                    <div style="min-width:0">
                        @if ($thread->unread)<span class="badge bg-primary">Neu</span>@endif
                        <a href="{{ route('band-manager.messages.show', $thread) }}" @class(['fw-bold' => $thread->unread])>{{ $thread->subject }}</a>
                        <br><small class="text-muted">{{ $thread->band->name }}</small>
                    </div>
                    <small class="text-muted text-nowrap">{{ $thread->last_message_at->format('d.m. H:i') }}</small>
                </li>
            @empty
                <li class="list-group-item text-muted">Noch keine Nachrichten.</li>
            @endforelse
        </ul>
    </div>
</div>
