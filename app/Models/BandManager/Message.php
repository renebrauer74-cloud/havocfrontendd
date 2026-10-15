<?php

namespace App\Models\BandManager;

use Database\Factories\BandManager\MessageFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Einzelne Nachricht in einer Unterhaltung zwischen Agentur und Band.
 */
#[Table('bm_messages')]
#[Fillable(['thread_id', 'user_id', 'body'])]
class Message extends Model
{
    /** @use HasFactory<MessageFactory> */
    use HasFactory;

    public const MAX_LENGTH = 5000;

    /**
     * @return BelongsTo<MessageThread, $this>
     */
    public function thread(): BelongsTo
    {
        return $this->belongsTo(MessageThread::class, 'thread_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isFrom(User $user): bool
    {
        return $this->user_id !== null && $this->user_id === $user->id;
    }
}
