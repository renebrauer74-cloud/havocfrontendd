@php($multi = $bands->count() > 1)
<x-band-manager.layout title="Dokumente">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <h1 class="h3 mb-0">Dokumente</h1>
        @if ($multi)
            <form method="get" action="{{ route('band-manager.documents.index') }}">
                <select name="band" class="form-select w-auto" onchange="this.form.submit()" aria-label="Band">
                    <option value="">Alle Bands</option>
                    @foreach ($bands as $band)
                        <option value="{{ $band->id }}" @selected($bandId === $band->id)>{{ $band->name }}</option>
                    @endforeach
                </select>
            </form>
        @endif
    </div>

    @if ($bands->isEmpty())
        <div class="alert alert-info">Dir ist noch keine Band zugeordnet. Bitte wende dich an die Agentur.</div>
    @else

        {{-- ================= VERTRÄGE ================= --}}
        <h2 class="h5 mt-4 mb-2" id="vertrag">Verträge</h2>

        @if ($canManage)
            <form method="post" action="{{ route('band-manager.documents.store') }}" enctype="multipart/form-data" class="card card-body shadow-sm mb-3">
                @csrf
                <input type="hidden" name="type" value="contract">
                <input type="hidden" name="filter" value="{{ $bandId }}">
                <div class="row g-3">
                    @include('band-manager.documents.band-select', ['id' => 'contract_band_id'])
                    @if ($gigs->isNotEmpty())
                        <div class="col-md-6">
                            <label class="form-label" for="gig_id">Gig (optional)</label>
                            <select name="gig_id" id="gig_id" class="form-select">
                                <option value="">– kein bestimmter Gig –</option>
                                @foreach ($gigs as $gig)
                                    <option value="{{ $gig->id }}" @selected($gigId === $gig->id)>
                                        {{ \Carbon\CarbonImmutable::parse($gig->date)->format('d.m.Y') }} · {{ $gig->band->name }} · {{ $gig->title }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    @endif
                    <x-band-manager.field name="title" label="Titel" placeholder="z.B. Vertrag Stadtfest 2025" :max="190" id="contract_title" />
                    <div class="col-md-6">
                        <label class="form-label" for="contract_file">PDF-Datei * <small class="text-muted">(max. {{ $maxMb }} MB)</small></label>
                        <input type="file" name="file" id="contract_file" class="form-control" accept="application/pdf,.pdf" required>
                    </div>
                </div>
                <div class="mt-3"><button class="btn btn-primary">Vertrag hinterlegen</button></div>
            </form>
        @endif

        <div class="card shadow-sm mb-4">
            @include('band-manager.documents.table', [
                'documents' => $contracts, 'showBand' => $multi, 'infoLabel' => 'Gig', 'empty' => 'Noch keine Verträge hinterlegt.',
            ])
        </div>

        {{-- ================= RIDER ================= --}}
        <h2 class="h5 mt-4 mb-2">Rider</h2>

        @if ($canUpload)
            <form method="post" action="{{ route('band-manager.documents.store') }}" enctype="multipart/form-data" class="card card-body shadow-sm mb-3">
                @csrf
                <input type="hidden" name="type" value="rider">
                <input type="hidden" name="filter" value="{{ $bandId }}">
                <div class="row g-3">
                    @include('band-manager.documents.band-select', ['id' => 'rider_band_id'])
                    <div class="col-md-6">
                        <label class="form-label" for="category">Art *</label>
                        <select name="category" id="category" class="form-select" required>
                            @foreach ($categories as $key => $label)
                                <option value="{{ $key }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="rider_title">Titel</label>
                        <input name="title" id="rider_title" class="form-control" maxlength="190" placeholder="z.B. Technical Rider 2025">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="rider_file">PDF-Datei * <small class="text-muted">(max. {{ $maxMb }} MB)</small></label>
                        <input type="file" name="file" id="rider_file" class="form-control" accept="application/pdf,.pdf" required>
                    </div>
                </div>
                <div class="mt-3"><button class="btn btn-primary">Rider hochladen</button></div>
            </form>
        @endif

        <div class="card shadow-sm mb-4">
            @include('band-manager.documents.table', [
                'documents' => $riders, 'showBand' => $multi, 'infoLabel' => 'Art', 'empty' => 'Noch keine Rider hochgeladen.',
            ])
        </div>

    @endif
</x-band-manager.layout>
