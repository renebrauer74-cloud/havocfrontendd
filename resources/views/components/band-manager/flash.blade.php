@props(['types', 'validationErrors' => []])
{{-- Eine Meldung pro Typ oder mehrere als Array (z. B. mehrere Hinweise nach dem Speichern eines Gigs) --}}
@foreach ($types as $key => [$class, $icon])
    @foreach ((array) session($key) as $message)
        <div class="alert alert-{{ $class }} hv-flash" role="alert"><i class="bi bi-{{ $icon }}"></i><span>{{ $message }}</span></div>
    @endforeach
@endforeach
@if ($validationErrors)
    <div class="alert alert-danger hv-flash" role="alert"><i class="bi bi-exclamation-octagon"></i><span>{{ implode(' ', $validationErrors) }}</span></div>
@endif
