<x-band-manager.layout title="CSV-Export">
    <a href="{{ route('band-manager.exports.index') }}" class="small">&larr; Export</a>
    <h1 class="h3 mb-4">CSV-Export</h1>
    <form method="get" action="{{ route('band-manager.admin.availability-export.download') }}" class="card card-body shadow-sm" style="max-width:520px">
        <div class="mb-3">
            <label class="form-label" for="month">Monat</label>
            <input type="month" name="month" id="month" class="form-control" value="{{ $month }}" required>
        </div>
        <div class="mb-3">
            <label class="form-label" for="band">Band</label>
            <select name="band" id="band" class="form-select">
                <option value="">Alle Bands</option>
                @foreach ($bands as $band)
                    <option value="{{ $band->id }}">{{ $band->name }}</option>
                @endforeach
            </select>
        </div>
        <button class="btn btn-primary">CSV herunterladen</button>
    </form>
</x-band-manager.layout>
