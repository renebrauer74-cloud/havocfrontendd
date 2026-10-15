<div class="col-lg-6">
    <div class="card shadow-sm h-100">
        <div class="card-header d-flex justify-content-between align-items-center">
            <span>Neueste Dokumente</span>
            <a href="{{ route('band-manager.documents.index') }}" class="small">Alle</a>
        </div>
        <ul class="list-group list-group-flush">
            @forelse ($latestDocuments as $document)
                <li class="list-group-item d-flex justify-content-between align-items-center gap-2">
                    <div>
                        <span @class(['badge', 'bg-primary' => $document->isContract(), 'bg-secondary' => ! $document->isContract()])>
                            {{ $document->isContract() ? 'Vertrag' : $document->categoryLabel() }}
                        </span>
                        <a href="{{ route('band-manager.documents.show', $document) }}" target="_blank" rel="noopener">{{ $document->title }}</a>
                        <br><small class="text-muted">{{ $document->band->name }}</small>
                    </div>
                    <small class="text-muted text-nowrap">{{ $document->created_at->format('d.m.Y') }}</small>
                </li>
            @empty
                <li class="list-group-item text-muted">Noch keine Dokumente.</li>
            @endforelse
        </ul>
    </div>
</div>
