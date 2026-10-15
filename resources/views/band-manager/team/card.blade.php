<a href="{{ route('band-manager.team.show', $person) }}" class="tm-card">
    <div class="tm-photo">
        @if ($person->photoUrl())
            <img src="{{ $person->photoUrl() }}" alt="{{ $person->fullName() }}" loading="lazy">
        @else
            <span class="tm-initials">{{ $person->initials() }}</span>
        @endif
    </div>
    <div class="tm-body">
        <div class="tm-name">{{ $person->fullName() }}</div>
        @if ($person->stage_name)<div class="tm-sub">„{{ $person->stage_name }}“</div>@endif
        <span class="tm-role">{{ $person->roleLabel() }}</span>
    </div>
</a>
