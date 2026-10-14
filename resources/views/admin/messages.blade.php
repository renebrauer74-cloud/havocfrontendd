<x-layouts.admin title="Anfragen">

<div class="dashboard-box">
    <h2>Anfragen</h2>
    <div class="subtitle">Nachrichten aus dem Kontaktformular – neueste zuerst.</div>

    <div class="management-list">
@forelse ($messages as $message)
        <div class="management-row message-row">
            <div>
                <span class="row-meta">{{ $message->created_at?->format('d.m.Y H:i') }}</span>
                <span class="row-title">{{ $message->name }}</span>
                <a class="row-meta" href="mailto:{{ $message->email }}">{{ $message->email }}</a>
                <p class="message-text">{!! nl2br(e($message->message), false) !!}</p>
            </div>
            <div class="action-group">
                <x-admin.delete-button :action="route('admin.messages.destroy', $message)" confirm="Anfrage löschen?" />
            </div>
        </div>
@empty
        <div class="list-empty">Keine Anfragen vorhanden.</div>
@endforelse
    </div>
</div>

</x-layouts.admin>
