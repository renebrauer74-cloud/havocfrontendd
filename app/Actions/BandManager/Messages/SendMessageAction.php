<?php

namespace App\Actions\BandManager\Messages;

use App\Models\BandManager\Message;
use App\Models\BandManager\MessageThread;
use App\Models\BandManager\User;

/**
 * Neue Nachricht in einer Unterhaltung – die eigene Nachricht gilt als gelesen.
 */
class SendMessageAction
{
    public function __construct(private MarkThreadReadAction $markRead) {}

    public function execute(MessageThread $thread, User $author, string $body): Message
    {
        $message = $thread->messages()->create(['user_id' => $author->id, 'body' => $body]);

        $thread->update(['last_message_at' => $message->created_at]);
        $this->markRead->execute($thread, $author, $message->id);

        return $message;
    }
}
