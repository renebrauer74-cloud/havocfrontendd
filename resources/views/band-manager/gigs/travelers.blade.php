{{-- Reisegruppe im Gig-Formular – je Band eine Liste, sichtbar ist nur die der gewählten Band --}}
@inject('describeRole', 'App\Actions\BandManager\Team\DescribeTeamMemberRoleAction')
@php
    $selectedBandId = $bands->count() === 1 ? $bands->first()->id : (int) old('band_id', $gig->band_id);
@endphp

<div class="col-12 border-top pt-3 mt-3">
    <h2 class="h5 mb-0"><i class="bi bi-people me-1"></i>Reisegruppe</h2>
    <div class="form-text mt-0">Wer reist wirklich mit? Nur die ausgewählten Personen stehen im Day Sheet.</div>
</div>

<div class="col-12">
    <div @class(['text-muted small', 'd-none' => $selectedBandId > 0]) data-travel-hint>Bitte zuerst eine Band wählen.</div>

    @foreach ($bands as $band)
        @php
            $people = $teamByBand->get($band->id, collect());
            $visible = $band->id === $selectedBandId;
        @endphp
        <div @class(['d-none' => ! $visible]) data-travel-band="{{ $band->id }}">
            @if ($people->isEmpty())
                <div class="text-muted small">
                    Für {{ $band->name }} sind noch keine Personen unter „Band &amp; Team“ angelegt.
                    @can('team.view')
                        <a href="{{ route('band-manager.team.band', $band) }}">Zu Band &amp; Team</a>
                    @endcan
                </div>
            @else
                <div class="mb-2 small">
                    <a href="#" data-travel-all="1">Alle auswählen</a> · <a href="#" data-travel-all="0">Keine</a>
                </div>
                <div class="row g-2">
                    @foreach ($people as $person)
                        <div class="col-md-6">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="travelers[]" value="{{ $person->id }}"
                                       id="traveler{{ $person->id }}" @checked(in_array($person->id, $travelerIds, true)) @disabled(! $visible)>
                                <label class="form-check-label" for="traveler{{ $person->id }}">
                                    {{ trim($person->first_name.' '.$person->last_name) }}
                                    <span class="text-muted small">
                                        · {{ $person->isCrew() ? 'Crew' : 'Band' }} · {{ $describeRole->execute($person) }}
                                    </span>
                                </label>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    @endforeach
    @error('travelers.*')
        <div class="text-danger small mt-1">{{ $message }}</div>
    @enderror
</div>

@push('scripts')
    <script>
        // Reisegruppe: nur die Personen der gewählten Band anzeigen und absenden
        (() => {
            const groups = document.querySelectorAll('[data-travel-band]');
            const hint = document.querySelector('[data-travel-hint]');
            const select = document.getElementById('band_id');

            if (select) {
                select.addEventListener('change', () => {
                    groups.forEach((group) => {
                        const visible = group.dataset.travelBand === select.value;
                        group.classList.toggle('d-none', !visible);
                        group.querySelectorAll('input').forEach((input) => { input.disabled = !visible; });
                    });
                    hint?.classList.toggle('d-none', select.value !== '');
                });
            }

            document.querySelectorAll('[data-travel-all]').forEach((link) => link.addEventListener('click', (event) => {
                event.preventDefault();
                link.closest('[data-travel-band]').querySelectorAll('input').forEach((input) => { input.checked = link.dataset.travelAll === '1'; });
            }));
        })();
    </script>
@endpush
