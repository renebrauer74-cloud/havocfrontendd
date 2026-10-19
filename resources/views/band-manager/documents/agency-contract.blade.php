{{-- Agenturvertrag einer Band: aktueller Vertrag, frühere Versionen aufklappbar --}}
@php($current = $entry['current'])
<div class="list-group-item">
    <div class="d-flex flex-wrap align-items-center gap-3">
        <i @class(['bi fs-4', 'bi-file-earmark-check text-primary' => $current, 'bi-file-earmark-x text-warning' => ! $current])></i>
        <div class="flex-grow-1">
            <div class="fw-semibold">{{ $entry['band']->name }}</div>
            @if ($current)
                <small class="text-muted">{{ $current->title }} · seit {{ $current->created_at->format('d.m.Y') }}</small>
            @else
                <small class="text-warning">Noch kein Agenturvertrag hinterlegt</small>
            @endif
        </div>
        <div class="d-flex flex-wrap gap-1">
            @if ($entry['previous']->isNotEmpty())
                <button class="btn btn-sm btn-outline-secondary" type="button" data-bs-toggle="collapse" data-bs-target="#agencyHistory{{ $entry['band']->id }}"
                        aria-expanded="false" aria-controls="agencyHistory{{ $entry['band']->id }}">
                    Frühere Versionen ({{ $entry['previous']->count() }})
                </button>
            @endif
            @if ($current)
                <a href="{{ route('band-manager.documents.show', $current) }}" target="_blank" rel="noopener" class="btn btn-sm btn-outline-secondary">Ansehen</a>
                <a href="{{ route('band-manager.documents.show', [$current, 'download' => 1]) }}" class="btn btn-sm btn-outline-secondary">Download</a>
                @can('delete', $current)
                    <form method="post" action="{{ route('band-manager.documents.destroy', $current) }}" class="d-inline"
                          onsubmit="return confirm('Agenturvertrag wirklich löschen?')">
                        @csrf
                        @method('DELETE')
                        <input type="hidden" name="filter" value="{{ $bandId }}">
                        <button class="btn btn-sm btn-outline-danger">Löschen</button>
                    </form>
                @endcan
            @elseif ($canManage)
                <button class="btn btn-sm btn-outline-primary" type="button" data-upload-agency="{{ $entry['band']->id }}">Hochladen</button>
            @endif
        </div>
    </div>
    @if ($entry['previous']->isNotEmpty())
        <div class="collapse mt-2" id="agencyHistory{{ $entry['band']->id }}">
            <div class="border rounded">
                @include('band-manager.documents.table', [
                    'documents' => $entry['previous'], 'showBand' => false, 'infoLabel' => null, 'empty' => '',
                ])
            </div>
        </div>
    @endif
</div>
