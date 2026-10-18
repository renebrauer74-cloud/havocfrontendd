@inject('describeRole', 'App\Actions\BandManager\Team\DescribeTeamMemberRoleAction')
<x-band-manager.layout :title="trim($person->first_name.' '.$person->last_name)">
    @push('styles')
        <link href="{{ asset('assets/band-manager/css/team.css') }}?v=2" rel="stylesheet">
    @endpush

    <a href="{{ route('band-manager.team.band', $band) }}" class="hv-back"><i class="bi bi-arrow-left"></i> {{ $band->name }} – Band &amp; Team</a>

    <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mt-1 mb-4">
        <div>
            <h1 class="h3 mb-1">{{ $person->first_name }} {{ $person->last_name }}</h1>
            <div class="text-muted">
                @if ($person->stage_name)„{{ $person->stage_name }}“ · @endif
                {{ $describeRole->execute($person) }}
                · {{ $person->isCrew() ? 'Team' : 'Band' }}
            </div>
        </div>
        @if ($canEdit)
            <div class="d-flex gap-2">
                <a href="{{ route('band-manager.team.edit', $person) }}" class="btn btn-primary"><i class="bi bi-pencil"></i> Bearbeiten</a>
                <form method="post" action="{{ route('band-manager.team.destroy', $person) }}" class="m-0"
                      onsubmit="return confirm('Diese Person inkl. Foto wirklich löschen?')">
                    @csrf
                    @method('DELETE')
                    <button class="btn btn-outline-danger"><i class="bi bi-trash3"></i> Löschen</button>
                </form>
            </div>
        @endif
    </div>

    <div class="row g-4">
        <div class="col-md-4 col-lg-3">
            <div class="tm-photo tm-photo--profile">
                @if ($person->hasMedia(\App\Enums\MediaCollectionEnum::MemberPhotos->value))
                    <img src="{{ route('band-manager.team.photo', $person) }}" alt="{{ $person->first_name }} {{ $person->last_name }}">
                @else
                    <x-band-manager.initials :person="$person" />
                @endif
            </div>
        </div>
        <div class="col-md-8 col-lg-9">
            <div class="card shadow-sm mb-4">
                <div class="card-header">Steckbrief</div>
                <div class="card-body">
                    @if ($person->bio)
                        {!! md($person->bio) !!}
                    @else
                        <span class="text-muted">Noch kein Steckbrief hinterlegt.</span>
                    @endif
                </div>
            </div>
            <div class="card shadow-sm">
                <div class="card-header">Stammdaten &amp; Tour-Infos</div>
                <div class="card-body">
                    @if ($facts)
                        <dl class="row tm-facts mb-0">
                            @foreach ($facts as [$label, $value, $href])
                                <div class="col-sm-6">
                                    <dt>{{ $label }}</dt>
                                    <dd>@if ($href)<a href="{{ $href }}" class="hv-link">{{ $value }}</a>@else{!! nl2br(e($value)) !!}@endif</dd>
                                </div>
                            @endforeach
                        </dl>
                    @else
                        <span class="text-muted">Noch keine Stammdaten hinterlegt.</span>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-band-manager.layout>
