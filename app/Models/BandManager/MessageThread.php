<?php

namespace App\Models\BandManager;

use Database\Factories\BandManager\MessageThreadFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Support\Facades\DB;

/**
 * Unterhaltung zwischen Agentur und einer Band mit Gelesen-Status pro Benutzer.
 * Bands können Unterhaltungen für sich ausblenden – bei einer neuen Nachricht tauchen sie wieder auf.
 */
#[Table('bm_message_threads')]
#[Fillable(['band_id', 'subject', 'created_by', 'last_message_at'])]
class MessageThread extends Model
{
    /** @use HasFactory<MessageThreadFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'last_message_at' => 'datetime',
            'unread' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<Band, $this>
     */
    public function band(): BelongsTo
    {
        return $this->belongsTo(Band::class);
    }

    /**
     * @return HasMany<Message, $this>
     */
    public function messages(): HasMany
    {
        return $this->hasMany(Message::class, 'thread_id');
    }

    /**
     * Unterhaltungen der Bands, die der Benutzer sehen darf – ohne die von ihm ausgeblendeten.
     *
     * @param  Builder<MessageThread>  $query
     */
    #[Scope]
    protected function visibleTo(Builder $query, User $user): void
    {
        $query
            ->whereIn('band_id', Band::query()->accessibleBy($user)->select('id'))
            ->whereNotExists(fn (QueryBuilder $hidden) => $hidden
                ->from('bm_message_hidden as h')
                ->whereColumn('h.thread_id', 'bm_message_threads.id')
                ->where('h.user_id', $user->id)
                ->whereRaw('h.up_to_id >= (SELECT COALESCE(MAX(m4.id), 0) FROM bm_messages m4 WHERE m4.thread_id = bm_message_threads.id)'));
    }

    /**
     * Nur Unterhaltungen mit Nachrichten anderer, die der Benutzer noch nicht gelesen hat.
     *
     * @param  Builder<MessageThread>  $query
     */
    #[Scope]
    protected function unreadFor(Builder $query, User $user): void
    {
        $query->whereExists(fn (QueryBuilder $messages) => $this->unreadMessages($messages, $user));
    }

    /**
     * Spalte "unread" (true/false) und "last_body" (Text der letzten Nachricht) für die Liste.
     *
     * @param  Builder<MessageThread>  $query
     */
    #[Scope]
    protected function withListInfo(Builder $query, User $user): void
    {
        $unread = $this->unreadMessages(DB::query(), $user);

        $query
            ->select('bm_message_threads.*')
            ->selectRaw('EXISTS ('.$unread->toSql().') AS unread', $unread->getBindings())
            ->addSelect(['last_body' => Message::query()
                ->select('body')
                ->whereColumn('thread_id', 'bm_message_threads.id')
                ->orderByDesc('id')
                ->limit(1)]);
    }

    private function unreadMessages(QueryBuilder $messages, User $user): QueryBuilder
    {
        return $messages
            ->selectRaw('1')
            ->from('bm_messages as m')
            ->leftJoin('bm_message_reads as r', fn (JoinClause $join) => $join
                ->on('r.thread_id', '=', 'm.thread_id')
                ->where('r.user_id', '=', $user->id))
            ->whereColumn('m.thread_id', 'bm_message_threads.id')
            ->where(fn (QueryBuilder $author) => $author->whereNull('m.user_id')->orWhere('m.user_id', '<>', $user->id))
            ->whereRaw('m.id > COALESCE(r.last_read_id, 0)');
    }
}
