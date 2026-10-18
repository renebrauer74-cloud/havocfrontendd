@inject('describeRole', 'App\Actions\BandManager\Team\DescribeTeamMemberRoleAction')
<a href="{{ route('band-manager.team.show', $person) }}" class="tm-card">
    <div class="tm-photo">
        @if ($person->hasMedia(\App\Enums\MediaCollectionEnum::MemberPhotos->value))
            <img src="{{ route('band-manager.team.photo', $person) }}" alt="{{ $person->first_name }} {{ $person->last_name }}" loading="lazy">
        @else
            <x-band-manager.initials :person="$person" />
        @endif
    </div>
    <div class="tm-body">
        <div class="tm-name">{{ $person->first_name }} {{ $person->last_name }}</div>
        @if ($person->stage_name)<div class="tm-sub">„{{ $person->stage_name }}“</div>@endif
        <span class="tm-role">{{ $describeRole->execute($person) }}</span>
    </div>
</a>
