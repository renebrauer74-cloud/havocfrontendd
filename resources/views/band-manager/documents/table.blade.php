<div class="table-responsive">
    <table class="table align-middle mb-0">
        <thead><tr>
            @if ($showBand)<th>Band</th>@endif
            <th>Titel</th><th>{{ $infoLabel }}</th><th>Hochgeladen</th>
            <th class="text-end">Größe</th><th class="text-end">Aktionen</th>
        </tr></thead>
        <tbody>
        @forelse ($documents as $document)
            <tr>
                @if ($showBand)<td>{{ $document->band->name }}</td>@endif
                <td>
                    {{ $document->title }}<br>
                    <small class="text-muted">{{ $document->getFirstMedia(\App\Enums\MediaCollectionEnum::Documents->value)?->file_name }}</small>
                </td>
                <td>
                    @if (! $document->isContract())
                        {{ $document->category?->label() ?? '–' }}
                    @elseif ($document->gig)
                        {{ \Carbon\CarbonImmutable::parse($document->gig->date)->format('d.m.Y') }} · {{ $document->gig->title }}
                    @else
                        <span class="text-muted">–</span>
                    @endif
                </td>
                <td class="text-nowrap">{{ $document->created_at->format('d.m.Y') }}</td>
                <td class="text-end text-nowrap">{{ $document->getFirstMedia(\App\Enums\MediaCollectionEnum::Documents->value)?->human_readable_size }}</td>
                <td class="text-end text-nowrap">
                    <a href="{{ route('band-manager.documents.show', $document) }}" target="_blank" rel="noopener" class="btn btn-sm btn-outline-secondary">Ansehen</a>
                    <a href="{{ route('band-manager.documents.show', [$document, 'download' => 1]) }}" class="btn btn-sm btn-outline-secondary">Download</a>
                    @can('delete', $document)
                        <form method="post" action="{{ route('band-manager.documents.destroy', $document) }}" class="d-inline"
                              onsubmit="return confirm('Dokument wirklich löschen?')">
                            @csrf
                            @method('DELETE')
                            <input type="hidden" name="filter" value="{{ $bandId }}">
                            <button class="btn btn-sm btn-outline-danger">Löschen</button>
                        </form>
                    @endcan
                </td>
            </tr>
        @empty
            <tr><td colspan="6" class="text-muted">{{ $empty }}</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
