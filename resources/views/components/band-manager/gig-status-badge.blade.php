@props(['gig'])
<span class="badge" style="background:{{ $gig->status->color() }}">{{ $gig->status->label() }}</span>
