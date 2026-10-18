<x-layouts.admin title="News">

<div class="dashboard-box">
    <x-admin.form-header :editing="(bool) $editing" :title="$editing ? 'News bearbeiten' : 'News verfassen'" />

    <form method="post" action="{{ $editing ? route('admin.news.update', $editing) : route('admin.news.store') }}" enctype="multipart/form-data">
        @csrf
        @if ($editing) @method('PUT') @endif

        <div class="form-group"><label for="newsTitle">Titel *</label><input type="text" id="newsTitle" name="title" required autocomplete="off" value="{{ old('title', $editing?->title) }}"></div>
        <div class="form-group"><label for="newsText">Inhalt *</label><textarea id="newsText" name="text" rows="6" required>{{ old('text', $editing?->text) }}</textarea></div>
        <div class="form-group">
            <label>News-Bild (Optional)</label>
            <x-admin.dropzone label="News-Bild ablegen oder klicken" :current="$editing?->getFirstMedia(\App\Enums\MediaCollectionEnum::News->value)?->getUrl()" removable />
        </div>

        <button type="submit" class="submit-btn">{{ $editing ? 'ÄNDERUNGEN SPEICHERN' : 'NEWS VERÖFFENTLICHEN' }}</button>
@if ($editing)
        <a class="cancel-btn" href="{{ route('admin.news.index') }}">ABBRECHEN</a>
@endif
    </form>
</div>

<div class="dashboard-box">
    <h2>News verwalten</h2>
    <div class="management-list">
@forelse ($news as $item)
        <div class="management-row">
            <div>
                <span class="row-meta">{{ $item->created_at?->format('d.m.Y') }}</span>
                <span class="row-title">{{ $item->title }}</span>
            </div>
            <div class="action-group">
                <a class="edit-btn" href="{{ route('admin.news.edit', $item) }}">EDITIEREN</a>
                <x-admin.delete-button :action="route('admin.news.destroy', $item)" confirm="News löschen?" />
            </div>
        </div>
@empty
        <div class="list-empty">Noch keine News veröffentlicht.</div>
@endforelse
    </div>
</div>

</x-layouts.admin>
