@php
    $isEdit = $band->exists;
    $selected = array_map('intval', old('members', $selectedUserIds));
@endphp
<x-band-manager.layout :title="$isEdit ? 'Band bearbeiten' : 'Neue Band'">
    <h1 class="h3 mb-4">{{ $isEdit ? 'Band bearbeiten' : 'Neue Band' }}</h1>

    <form method="post" action="{{ $isEdit ? route('band-manager.admin.bands.update', $band) : route('band-manager.admin.bands.store') }}"
          class="card card-body shadow-sm" style="max-width:760px">
        @csrf
        @if ($isEdit)
            @method('PUT')
        @endif
        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label" for="name">Bandname *</label>
                <input name="name" id="name" class="form-control" required value="{{ old('name', $band->name) }}">
            </div>
            <div class="col-md-6">
                <label class="form-label" for="genre">Genre</label>
                <input name="genre" id="genre" class="form-control" value="{{ old('genre', $band->genre) }}">
            </div>
            <div class="col-12">
                <label class="form-label" for="contact_email">Kontakt-E-Mail</label>
                <input type="email" name="contact_email" id="contact_email" class="form-control" value="{{ old('contact_email', $band->contact_email) }}">
            </div>
            <div class="col-12">
                <label class="form-label" for="description">Beschreibung</label>
                <textarea name="description" id="description" class="form-control" rows="3">{{ old('description', $band->description) }}</textarea>
            </div>
            <div class="col-12">
                <label class="form-label">Benutzer mit Zugriff auf diese Band</label>
                <div class="row">
                    @foreach ($users as $user)
                        <div class="col-sm-6 col-md-4">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="members[]" value="{{ $user->id }}" id="user-{{ $user->id }}"
                                    @checked(in_array($user->id, $selected, true))>
                                <label class="form-check-label" for="user-{{ $user->id }}">{{ $user->name }}</label>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
        <div class="mt-4 d-flex gap-2">
            <button class="btn btn-primary">Speichern</button>
            <a href="{{ route('band-manager.admin.bands.index') }}" class="btn btn-secondary">Abbrechen</a>
        </div>
    </form>
</x-band-manager.layout>
