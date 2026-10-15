{{-- Verträge zu diesem Gig (nur Agentur, beim Bearbeiten) --}}
<div class="col-12">
    <label class="form-label">Verträge zu diesem Gig</label>
    @if ($gig->contracts->isNotEmpty())
        <ul class="list-unstyled mb-1">
            @foreach ($gig->contracts->sortByDesc("created_at") as $contract)
                <li>📄 <a href="{{ route('band-manager.documents.show', $contract) }}" target="_blank" rel="noopener">{{ $contract->title }}</a>
                    <small class="text-muted">({{ $contract->created_at->format('d.m.Y') }})</small></li>
            @endforeach
        </ul>
    @else
        <div class="text-muted small mb-1">Noch kein Vertrag verknüpft.</div>
    @endif
    <a href="{{ route('band-manager.documents.index', ['band' => $gig->band_id, 'gig' => $gig->id]) }}#vertrag" class="small">+ Vertrag für diesen Gig hochladen</a>
</div>
