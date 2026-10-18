<x-band-manager.layout title="Neue Nachricht">
    <a href="{{ route('band-manager.messages.index') }}" class="hv-back"><i class="bi bi-arrow-left"></i> Alle Nachrichten</a>
    <h1 class="h3 mb-4">Neue Nachricht</h1>

    @if ($bands->isEmpty())
        <div class="alert alert-info">Dir ist noch keine Band zugeordnet. Bitte wende dich an die Agentur.</div>
    @else
        <div class="alert alert-info" style="max-width:760px">
            @if ($seesAll)
                Die Nachricht sehen <strong>alle Logins der gewählten Band</strong>.
            @else
                Deine Nachricht geht an die <strong>Agentur</strong>.
            @endif
        </div>

        <form method="post" action="{{ route('band-manager.messages.store') }}" class="card card-body shadow-sm" style="max-width:760px">
            @csrf
            <div class="row g-3">
                @if ($bands->count() === 1)
                    <input type="hidden" name="band_id" value="{{ $bands->first()->id }}">
                @else
                    <div class="col-md-6">
                        <label class="form-label" for="band_id">{{ $seesAll ? 'An Band *' : 'Für Band *' }}</label>
                        <select name="band_id" id="band_id" class="form-select" required>
                            <option value="">– bitte wählen –</option>
                            @foreach ($bands as $band)
                                <option value="{{ $band->id }}" @selected((int) old('band_id', $bandId) === $band->id)>{{ $band->name }}</option>
                            @endforeach
                        </select>
                    </div>
                @endif
                <x-band-manager.field name="subject" label="Betreff *" col="col-12" :max="190" required />
                <x-band-manager.textarea name="body" label="Nachricht *" :rows="6" :max="5000" required />
            </div>
            <div class="mt-4 d-flex gap-2">
                <button class="btn btn-primary">Senden</button>
                <a href="{{ route('band-manager.messages.index') }}" class="btn btn-secondary">Abbrechen</a>
            </div>
        </form>
    @endif
</x-band-manager.layout>
