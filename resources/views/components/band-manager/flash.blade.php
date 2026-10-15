@props(['types', 'validationErrors' => []])
@foreach ($types as $key => [$class, $icon])
    @if (session()->has($key))
        <div class="alert alert-{{ $class }} hv-flash" role="alert"><i class="bi bi-{{ $icon }}"></i><span>{{ session($key) }}</span></div>
    @endif
@endforeach
@if ($validationErrors)
    <div class="alert alert-danger hv-flash" role="alert"><i class="bi bi-exclamation-octagon"></i><span>{{ implode(' ', $validationErrors) }}</span></div>
@endif
