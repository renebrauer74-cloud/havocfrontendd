<?php

namespace App\Actions\BandManager\Messages;

use App\Models\BandManager\MessageThread;
use App\Models\BandManager\User;
use Illuminate\Support\Facades\DB;

/**
 * Blendet eine Unterhaltung nur für diesen Benutzer aus – bis zur nächsten neuen Nachricht.
 */
class HideThreadAction
{
    public function __construct(private MarkThreadReadAction $markRead) {}

    public function execute(MessageThread $thread, User $user): void
    {
        $lastId = (int) $thread->messages()->max('id');

        DB::table('bm_message_hidden')->upsert(
            [['thread_id' => $thread->id, 'user_id' => $user->id, 'up_to_id' => $lastId]],
            ['thread_id', 'user_id'],
            ['up_to_id'],
        );

        if ($lastId) {
            $this->markRead->execute($thread, $user, $lastId);
        }
    }
}
