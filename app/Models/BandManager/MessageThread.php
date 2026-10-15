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
            ->whereIn('band_id', $user->accessibleBands()->select('id'))
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

    public static function unreadCountFor(User $user): int
    {
        return static::query()->visibleTo($user)->unreadFor($user)->count();
    }

    public function markReadFor(User $user, int $messageId): void
    {
        $current = DB::table('bm_message_reads')->where('thread_id', $this->id)->where('user_id', $user->id)->value('last_read_id');

        if ($current === null) {
            DB::table('bm_message_reads')->insert(['thread_id' => $this->id, 'user_id' => $user->id, 'last_read_id' => $messageId]);
        } elseif ($messageId > $current) {
            DB::table('bm_message_reads')->where('thread_id', $this->id)->where('user_id', $user->id)->update(['last_read_id' => $messageId]);
        }
    }

    /**
     * Nur für diesen Benutzer ausblenden – bis zur nächsten neuen Nachricht.
     */
    public function hideFor(User $user): void
    {
        $lastId = (int) $this->messages()->max('id');

        DB::table('bm_message_hidden')->upsert(
            [['thread_id' => $this->id, 'user_id' => $user->id, 'up_to_id' => $lastId]],
            ['thread_id', 'user_id'],
            ['up_to_id'],
        );

        if ($lastId) {
            $this->markReadFor($user, $lastId);
        }
    }

    /**
     * Neue Nachricht senden – die eigene Nachricht gilt als gelesen.
     */
    public function send(User $author, string $body): Message
    {
        $message = $this->messages()->create(['user_id' => $author->id, 'body' => $body]);

        $this->update(['last_message_at' => $message->created_at]);
        $this->markReadFor($author, $message->id);

        return $message;
    }

    /**
     * Nach dem Löschen einer Nachricht: Zeitpunkt der letzten verbliebenen Nachricht übernehmen.
     */
    public function refreshLastMessageAt(): void
    {
        $this->update(['last_message_at' => $this->messages()->max('created_at') ?? $this->created_at]);
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
