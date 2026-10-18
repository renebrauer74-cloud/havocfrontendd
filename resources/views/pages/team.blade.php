<x-layouts.site title="HAVOC AGENCY | TEAM" layout="editorial" :styles="['team.css']">

    <div class="content-container">
        <main>
            <h1 class="page-title">OUR TEAM //</h1>

            <div class="team-grid">
@forelse ($members as $member)
                <div class="team-card">
                    <div class="profile-wrapper">
                        <img src="{{ $member->getFirstMediaUrl(\App\Enums\MediaCollectionEnum::Team->value) }}" alt="{{ $member->name }}" class="profile-img">
                    </div>
                    <h2 class="member-name">{{ $member->name }}</h2>

                    <div class="metadata-group">
                        <div class="metadata-row"><span class="metadata-label">FON //</span><span class="metadata-value">{{ $member->phone ?: '—' }}</span></div>
                        <div class="metadata-row"><span class="metadata-label">MAIL //</span><span class="metadata-value"><a href="mailto:{{ $member->email }}">{{ $member->email }}</a></span></div>
                        <div class="metadata-row"><span class="metadata-label">HOBBY //</span><span class="metadata-value">{{ $member->hobbies ?: '—' }}</span></div>
                    </div>

                    <p class="member-bio">{!! nl2br(e($member->bio), false) !!}</p>
                </div>
@empty
                <p class="list-empty">Keine Teammitglieder eingetragen.</p>
@endforelse
            </div>
        </main>
    </div>

</x-layouts.site>
