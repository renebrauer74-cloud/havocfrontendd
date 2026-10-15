{{-- Auswahlfeld zum Wechseln der Band – springt auf dieselbe Seite der gewählten Band --}}
@props(['bands', 'current', 'route'])
<select class="form-select w-auto" aria-label="Band wechseln" onchange="location.href = this.value">
    @foreach ($bands as $band)
        <option value="{{ route($route, $band) }}" @selected($band->is($current))>{{ $band->name }}</option>
    @endforeach
</select>
