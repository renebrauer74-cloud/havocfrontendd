<?php

namespace App\Actions\BandManager\Messages;

use App\Models\BandManager\MessageThread;

/**
 * Nach dem Löschen einer Nachricht: Zeitpunkt der letzten verbliebenen Nachricht übernehmen.
 */
class RefreshLastMessageAtAction
{
    public function execute(MessageThread $thread): void
    {
        $thread->update(['last_message_at' => $thread->messages()->max('created_at') ?? $thread->created_at]);
    }
}
