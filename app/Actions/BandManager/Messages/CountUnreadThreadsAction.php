<?php

namespace App\Actions\BandManager\Messages;

use App\Models\BandManager\MessageThread;
use App\Models\BandManager\User;

/**
 * Anzahl sichtbarer Unterhaltungen mit ungelesenen Nachrichten anderer.
 */
class CountUnreadThreadsAction
{
    public function execute(User $user): int
    {
        return MessageThread::query()->visibleTo($user)->unreadFor($user)->count();
    }
}
