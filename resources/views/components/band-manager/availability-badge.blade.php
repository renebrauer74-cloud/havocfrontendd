@props(['availability' => null])
@if ($availability)
    <span class="badge" style="background:{{ $availability->status->color() }}">{{ $availability->status->label() }}</span>
@else
    <span class="badge text-bg-light border">Keine Angabe</span>
@endif
