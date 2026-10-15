@props(['gig'])
<span class="badge" style="background:{{ $gig->statusDefinition()['color'] }}">{{ $gig->statusDefinition()['label'] }}</span>
