@php
    use App\Enums\BandManager\ContractKindEnum;

    $multi = $bands->count() > 1;
    $selectedKind = ContractKindEnum::tryFrom((string) old('contract_kind')) ?? ($gigId ? ContractKindEnum::Gig : ContractKindEnum::Agency);
    $selectedGigId = (int) old('gig_id', $gigId);
    $openContractForm = $gigId || request()->boolean('upload') || ($errors->any() && old('type') === 'contract');
    $openRiderForm = $errors->any() && old('type') === 'rider';
@endphp
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
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mt-4 mb-2">
            <h2 class="h5 mb-0" id="vertrag">Verträge</h2>
            @if ($canManage)
                <button class="btn btn-sm btn-primary" type="button" data-bs-toggle="collapse" data-bs-target="#contractUpload"
                        aria-expanded="{{ $openContractForm ? 'true' : 'false' }}" aria-controls="contractUpload">
                    <i class="bi bi-plus-lg"></i> Vertrag hochladen
                </button>
            @endif
        </div>

        @if ($canManage)
            <div @class(['collapse', 'show' => $openContractForm]) id="contractUpload">
                <form method="post" action="{{ route('band-manager.documents.store') }}" enctype="multipart/form-data" class="card card-body shadow-sm mb-3" data-contract-form>
                    @csrf
                    <input type="hidden" name="type" value="contract">
                    <input type="hidden" name="filter" value="{{ $bandId }}">
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label d-block">Art des Vertrags *</label>
                            <div class="btn-group" role="group" aria-label="Art des Vertrags">
                                @foreach ($contractKinds as $kind)
                                    <input type="radio" class="btn-check" name="contract_kind" id="contract_kind_{{ $kind->value }}" value="{{ $kind->value }}"
                                           autocomplete="off" data-contract-kind @checked($selectedKind === $kind)>
                                    <label class="btn btn-outline-primary" for="contract_kind_{{ $kind->value }}">{{ $kind->label() }}</label>
                                @endforeach
                            </div>
                            <div data-kind-hint="{{ ContractKindEnum::Agency->value }}" @class(['form-text', 'd-none' => $selectedKind !== ContractKindEnum::Agency])>
                                Wird der aktuelle Agenturvertrag der Band – der bisherige bleibt unter „Frühere Versionen“ erhalten.
                            </div>
                            <div data-kind-hint="{{ ContractKindEnum::Gig->value }}" @class(['form-text', 'd-none' => $selectedKind !== ContractKindEnum::Gig])>
                                Erscheint unter Gastspielverträge im Jahr des Gigs. Den Gig bitte vorher unter „Gigs“ anlegen.
                            </div>
                        </div>
                        @include('band-manager.documents.band-select', ['id' => 'contract_band_id'])
                        <div data-gig-field @class(['col-md-6', 'd-none' => $selectedKind !== ContractKindEnum::Gig])>
                            <label class="form-label" for="gig_id">Gig *</label>
                            @if ($gigs->isNotEmpty())
                                <select name="gig_id" id="gig_id" class="form-select" @disabled($selectedKind !== ContractKindEnum::Gig) required>
                                    <option value="">– bitte wählen –</option>
                                    @foreach ($gigs as $gig)
                                        <option value="{{ $gig->id }}" @selected($selectedGigId === $gig->id)>
                                            {{ \Carbon\CarbonImmutable::parse($gig->date)->format('d.m.Y') }} · {{ $gig->band->name }} · {{ $gig->title }}
                                        </option>
                                    @endforeach
                                </select>
                            @else
                                <div class="form-text mt-0">Noch keine Gigs angelegt. <a href="{{ route('band-manager.gigs.create') }}" class="hv-link">Gig anlegen</a></div>
                            @endif
                        </div>
                        <x-band-manager.field name="title" label="Titel" placeholder="Optional – sonst der Dateiname" :max="190" id="contract_title" />
                        <div class="col-md-6">
                            <label class="form-label" for="contract_file">PDF-Datei * <small class="text-muted">(max. {{ $maxMb }} MB)</small></label>
                            <input type="file" name="file" id="contract_file" class="form-control" accept="application/pdf,.pdf" required>
                        </div>
                    </div>
                    <div class="mt-3"><button class="btn btn-primary">Vertrag hinterlegen</button></div>
                </form>
            </div>
        @endif

        {{-- Agenturverträge: je Band der aktuelle, frühere Versionen aufklappbar --}}
        <h3 class="h6 mb-2"><i class="bi bi-pin-angle"></i> Agenturverträge</h3>
        <div class="card shadow-sm mb-3">
            <div class="list-group list-group-flush">
                @foreach ($agencyContracts as $entry)
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
                @endforeach
            </div>
        </div>

        {{-- Gastspielverträge nach Jahr des Gigs --}}
        <h3 class="h6 mb-2"><i class="bi bi-calendar-event"></i> Gastspielverträge</h3>
        @if ($gigContractsByYear->isEmpty())
            <div class="card card-body shadow-sm mb-4 text-muted">Noch keine Gastspielverträge hinterlegt.</div>
        @else
            <div class="accordion shadow-sm mb-4" id="gigContracts">
                @foreach ($gigContractsByYear as $year => $yearContracts)
                    <div class="accordion-item">
                        <h4 class="accordion-header">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#gigContracts{{ $loop->index }}"
                                    aria-expanded="false" aria-controls="gigContracts{{ $loop->index }}">
                                <span class="fw-semibold">{{ $year }}</span>
                                <span class="badge text-bg-light border ms-2 fw-normal">{{ $yearContracts->count() }} {{ $yearContracts->count() === 1 ? 'Vertrag' : 'Verträge' }}</span>
                            </button>
                        </h4>
                        <div id="gigContracts{{ $loop->index }}" class="accordion-collapse collapse">
                            @include('band-manager.documents.table', [
                                'documents' => $yearContracts, 'showBand' => $multi, 'infoLabel' => 'Gig', 'empty' => '',
                            ])
                        </div>
                    </div>
                @endforeach
            </div>
        @endif

        {{-- ================= RIDER ================= --}}
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mt-4 mb-2">
            <h2 class="h5 mb-0">Rider</h2>
            @if ($canUpload)
                <button class="btn btn-sm btn-primary" type="button" data-bs-toggle="collapse" data-bs-target="#riderUpload"
                        aria-expanded="{{ $openRiderForm ? 'true' : 'false' }}" aria-controls="riderUpload">
                    <i class="bi bi-plus-lg"></i> Rider hochladen
                </button>
            @endif
        </div>

        @if ($canUpload)
            <div @class(['collapse', 'show' => $openRiderForm]) id="riderUpload">
                <form method="post" action="{{ route('band-manager.documents.store') }}" enctype="multipart/form-data" class="card card-body shadow-sm mb-3">
                    @csrf
                    <input type="hidden" name="type" value="rider">
                    <input type="hidden" name="filter" value="{{ $bandId }}">
                    <div class="row g-3">
                        @include('band-manager.documents.band-select', ['id' => 'rider_band_id'])
                        <div class="col-md-6">
                            <label class="form-label" for="category">Art *</label>
                            <select name="category" id="category" class="form-select" required>
                                @foreach ($categories as $category)
                                    <option value="{{ $category->value }}">{{ $category->label() }}</option>
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
                    <div class="mt-3"><button class="btn btn-primary">Rider speichern</button></div>
                </form>
            </div>
        @endif

        <div class="card shadow-sm mb-4">
            @include('band-manager.documents.table', [
                'documents' => $riders, 'showBand' => $multi, 'infoLabel' => 'Art', 'empty' => 'Noch keine Rider hochgeladen.',
            ])
        </div>

    @endif

    @if ($canManage && $bands->isNotEmpty())
        @push('scripts')
            <script>
                // Vertrags-Upload: Gig-Feld nur beim Gastspielvertrag; „Hochladen“ bei einer Band ohne Agenturvertrag öffnet das Formular
                (() => {
                    const form = document.querySelector('[data-contract-form]');
                    const gigField = form.querySelector('[data-gig-field]');
                    const gigSelect = gigField.querySelector('select');
                    const kinds = form.querySelectorAll('[data-contract-kind]');

                    const showKind = (kind) => {
                        gigField.classList.toggle('d-none', kind !== 'gig');
                        if (gigSelect) { gigSelect.disabled = kind !== 'gig'; }
                        form.querySelectorAll('[data-kind-hint]').forEach((hint) => hint.classList.toggle('d-none', hint.dataset.kindHint !== kind));
                    };

                    kinds.forEach((radio) => radio.addEventListener('change', () => showKind(radio.value)));

                    document.querySelectorAll('[data-upload-agency]').forEach((button) => button.addEventListener('click', () => {
                        const agency = form.querySelector('[data-contract-kind][value="agency"]');
                        agency.checked = true;
                        showKind('agency');
                        const bandSelect = form.querySelector('#contract_band_id');
                        if (bandSelect) { bandSelect.value = button.dataset.uploadAgency; }
                        bootstrap.Collapse.getOrCreateInstance(document.getElementById('contractUpload')).show();
                        document.getElementById('vertrag').scrollIntoView({ behavior: 'smooth' });
                    }));
                })();
            </script>
        @endpush
    @endif
</x-band-manager.layout>
