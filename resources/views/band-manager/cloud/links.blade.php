@inject('checkAccess', 'App\Actions\BandManager\Cloud\CheckCloudLinkAccessAction')
@php
    use App\Enums\MediaCollectionEnum;
@endphp
<x-band-manager.layout :title="'Freigaben – '.$band->name">
    @push('styles')
        <link href="{{ asset('assets/band-manager/css/cloud.css') }}?v=4" rel="stylesheet">
    @endpush

    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <div>
            <a href="{{ route('band-manager.cloud.band', $band) }}" class="hv-back"><i class="bi bi-arrow-left"></i> Zur Cloud</a>
            <h1 class="h3 mb-0">{{ $band->name }} – Freigaben</h1>
        </div>
    </div>

    <p class="text-muted small">
        <i class="bi bi-info-circle"></i> Über einen Freigabe-Link können Externe ohne Login ansehen und herunterladen – hochladen oder löschen
        können sie nie. Links legst du im Speicher über <i class="bi bi-share"></i> an. Ein widerrufener Link funktioniert sofort nicht mehr.
        Fotos von Fans &amp; Fotografen sammeln: <a href="{{ route('band-manager.photo-drops.band', $band) }}" class="hv-link"><i class="bi bi-camera"></i> Photo Drop</a>.
    </p>

    <div class="d-flex flex-column gap-3">
        @forelse ($links as $link)
            @php
                $media = $link->file?->getFirstMedia(MediaCollectionEnum::CloudFiles->value);
                $url = route('band-manager.share.show', $link);
                $usable = $checkAccess->execute($link);
            @endphp
            <div @class(['card card-body shadow-sm', 'cl-link-new' => $link->id === $newLinkId])>
                <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-2">
                    <span class="cl-name">
                        @if ($link->file_id)
                            <span class="cl-icon"><x-band-manager.cloud-file-icon :mime="$media?->mime_type" /></span>
                            <span class="cl-file-name fw-semibold">{{ $media?->file_name ?? 'Datei im Papierkorb' }}</span>
                        @else
                            <span class="cl-icon cl-icon--folder"><i class="bi bi-folder-fill"></i></span>
                            <span class="fw-semibold">{{ $link->folder?->name ?? 'Ordner im Papierkorb' }}</span>
                        @endif
                        @if ($link->isExpired())
                            <span class="badge text-bg-secondary">Abgelaufen</span>
                        @elseif (! $usable)
                            <span class="badge text-bg-warning">Ziel liegt im Papierkorb</span>
                        @endif
                    </span>
                    <form method="post" action="{{ route('band-manager.cloud.links.destroy', $link) }}" class="m-0"
                          onsubmit="return confirm('Link widerrufen? Er funktioniert dann sofort nicht mehr.')">
                        @csrf
                        @method('DELETE')
                        <button class="btn btn-sm btn-outline-danger"><i class="bi bi-x-circle"></i> {{ $usable ? 'Widerrufen' : 'Entfernen' }}</button>
                    </form>
                </div>

                @if ($usable)
                    <div class="input-group mb-2">
                        <input type="text" class="form-control" value="{{ $url }}" readonly aria-label="Link" data-cl-link-input>
                        <button class="btn btn-outline-primary" type="button" data-cl-copy><i class="bi bi-clipboard"></i> Kopieren</button>
                    </div>
                @endif

                @if ($link->message)
                    <div class="small fst-italic mb-2"><i class="bi bi-chat-quote"></i> {{ $link->message }}</div>
                @endif

                <div class="small text-muted d-flex flex-wrap column-gap-3 row-gap-1">
                    <span><i class="bi bi-{{ $link->type->icon() }}"></i> {{ $link->type->label() }}</span>
                    <span><i class="bi bi-hourglass-split"></i> gültig bis {{ $link->expires_at?->format('d.m.Y') ?? 'unbegrenzt' }}</span>
                    <span><i class="bi bi-clock-history"></i> zuletzt geöffnet: {{ $link->last_used_at?->format('d.m.Y H:i') ?? 'noch nie' }}</span>
                    <span>erstellt {{ $link->created_at->format('d.m.Y') }}@if ($link->creator) von {{ $link->creator->name }}@endif</span>
                </div>
            </div>
        @empty
            <div class="card card-body shadow-sm text-center text-muted py-4"><i class="bi bi-link-45deg fs-3 d-block mb-1"></i>Noch keine Freigaben.</div>
        @endforelse
    </div>

    @push('scripts')
        <script>
            document.querySelectorAll('[data-cl-copy]').forEach(button => button.addEventListener('click', async () => {
                const input = button.parentElement.querySelector('[data-cl-link-input]');
                try {
                    await navigator.clipboard.writeText(input.value);
                } catch {
                    input.select();
                    document.execCommand('copy');
                }
                button.innerHTML = '<i class="bi bi-check-lg"></i> Kopiert';
                setTimeout(() => { button.innerHTML = '<i class="bi bi-clipboard"></i> Kopieren'; }, 2000);
            }));
        </script>
    @endpush
</x-band-manager.layout>
