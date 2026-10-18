<?php

namespace App\Actions\BandManager\Messages;

use App\Models\BandManager\MessageThread;
use App\Models\BandManager\User;
use Illuminate\Support\Facades\DB;

/**
 * Merkt sich die zuletzt gelesene Nachricht eines Benutzers in einer Unterhaltung – nie rückwärts.
 */
class MarkThreadReadAction
{
    public function execute(MessageThread $thread, User $user, int $messageId): void
    {
        $current = DB::table('bm_message_reads')->where('thread_id', $thread->id)->where('user_id', $user->id)->value('last_read_id');

        if ($current === null) {
            DB::table('bm_message_reads')->insert(['thread_id' => $thread->id, 'user_id' => $user->id, 'last_read_id' => $messageId]);
        } elseif ($messageId > $current) {
            DB::table('bm_message_reads')->where('thread_id', $thread->id)->where('user_id', $user->id)->update(['last_read_id' => $messageId]);
        }
    }
}
