<x-layouts.admin title="Team">

<div class="dashboard-box">
    <x-admin.form-header :editing="(bool) $editing" :title="$editing ? 'Teammitglied bearbeiten' : 'Teammitglied hinzufügen'" subtitle="Internes Profil für die Agentur-Teamseite." />

    <form method="post" action="{{ $editing ? route('admin.team.update', $editing) : route('admin.team.store') }}" enctype="multipart/form-data">
        @csrf
        @if ($editing) @method('PUT') @endif

        <div class="form-group"><label for="teamName">Name *</label><input type="text" id="teamName" name="name" required autocomplete="off" value="{{ old('name', $editing?->name) }}"></div>
        <div class="form-group"><label for="teamPhone">Telefonnummer</label><input type="text" id="teamPhone" name="phone" placeholder="z.B. +49 (0) 123 45678" value="{{ old('phone', $editing?->phone) }}"></div>
        <div class="form-group"><label for="teamEmail">E-Mail-Adresse *</label><input type="email" id="teamEmail" name="email" required autocomplete="off" value="{{ old('email', $editing?->email) }}"></div>
        <div class="form-group"><label for="teamHobbies">Hobbys (mit Komma trennen)</label><input type="text" id="teamHobbies" name="hobbies" placeholder="z.B. Vinyl, Konzerte, Skaten" value="{{ old('hobbies', $editing?->hobbies) }}"></div>
        <div class="form-group"><label for="teamBio">Infotext / Biografie</label><textarea id="teamBio" name="bio" rows="6" placeholder="Beschreibe das Teammitglied...">{{ old('bio', $editing?->bio) }}</textarea></div>
        <div class="form-group">
            <label>Profilbild (Drag & Drop)</label>
            <x-admin.dropzone label="Profilbild ablegen oder klicken" :current="$editing?->image_url" />
        </div>

        <button type="submit" class="submit-btn">{{ $editing ? 'ÄNDERUNGEN SPEICHERN' : 'MITGLIED SPEICHERN' }}</button>
@if ($editing)
        <a class="cancel-btn" href="{{ route('admin.team.index') }}">ABBRECHEN</a>
@endif
    </form>
</div>

<div class="dashboard-box">
    <h2>Teammitglieder verwalten</h2>
    <div class="management-list">
@forelse ($members as $member)
        <div class="management-row">
            <div>
                <span class="row-title">{{ $member->name }}</span>
                <span class="row-meta">[{{ $member->email }}]</span>
@if ($member->bio)
                <p class="row-excerpt">{{ mb_strimwidth($member->bio, 0, 60, '...') }}</p>
@endif
            </div>
            <div class="action-group">
                <a class="edit-btn" href="{{ route('admin.team.edit', $member) }}">EDITIEREN</a>
                <x-admin.delete-button :action="route('admin.team.destroy', $member)" confirm="Teammitglied unwiderruflich löschen?" />
            </div>
        </div>
@empty
        <div class="list-empty">Keine Teammitglieder eingetragen.</div>
@endforelse
    </div>
</div>

</x-layouts.admin>
