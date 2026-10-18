<?php

namespace App\Http\Controllers\BandManager;

use App\Actions\BandManager\Messages\HideThreadAction;
use App\Actions\BandManager\Messages\MarkThreadReadAction;
use App\Actions\BandManager\Messages\RefreshLastMessageAtAction;
use App\Actions\BandManager\Messages\SendMessageAction;
use App\Actions\BandManager\RecordActivityAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\BandManager\MessageThreadRequest;
use App\Models\BandManager\Band;
use App\Models\BandManager\Message;
use App\Models\BandManager\MessageThread;
use App\Services\BandManager\Impersonation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

/**
 * Nachrichten zwischen Agentur und Bands – pro Band, mit Betreff und Gelesen-Status.
 */
class MessageController extends Controller
{
    public function __construct(private Impersonation $impersonation) {}

    public function index(Request $request): View
    {
        $user = $request->user();
        $band = $this->bandFilter($request);

        return view('band-manager.messages.index', [
            'threads' => MessageThread::query()
                ->with('band')
                ->visibleTo($user)
                ->withListInfo($user)
                ->when($band, fn ($query) => $query->where('band_id', $band->id))
                ->orderByDesc('last_message_at')
                ->orderByDesc('id')
                ->get(),
            'bands' => Band::query()->accessibleBy($user)->orderBy('name')->get(),
            'bandId' => $band?->id,
        ]);
    }

    public function create(Request $request): View
    {
        return view('band-manager.messages.create', [
            'bands' => Band::query()->accessibleBy($request->user())->orderBy('name')->get(),
            'bandId' => $request->integer('band'),
            'seesAll' => $request->user()->can('messages.manage'),
        ]);
    }

    public function store(MessageThreadRequest $request): RedirectResponse
    {
        $user = $request->user();

        $thread = MessageThread::query()->create([
            'band_id' => $request->validated('band_id'),
            'subject' => $request->validated('subject'),
            'created_by' => $user->id,
        ]);
        app(SendMessageAction::class)->execute($thread, $user, $request->validated('body'));

        app(RecordActivityAction::class)->execute('message.sent', ['thread_id' => $thread->id, 'band_id' => $thread->band_id]);

        $response = to_route('band-manager.messages.show', $thread)->with('success', 'Nachricht wurde gesendet.');

        if ($user->can('messages.manage') && ! $thread->band->users()->exists()) {
            $response->with('warning', 'Hinweis: Dieser Band ist noch kein Login zugeordnet – die Nachricht kann dort noch niemand lesen.');
        }

        return $response;
    }

    public function show(Request $request, MessageThread $thread): View
    {
        Gate::authorize('access', $thread->band);

        $messages = $thread->messages()->with('user')->orderBy('id')->get();

        // Beim "Einloggen als" nichts für die Band als gelesen markieren
        if ($messages->isNotEmpty() && ! $this->impersonation->isActive()) {
            app(MarkThreadReadAction::class)->execute($thread, $request->user(), $messages->last()->id);
        }

        return view('band-manager.messages.show', [
            'thread' => $thread,
            'messages' => $messages,
            'seesAll' => $request->user()->can('messages.manage'),
        ]);
    }

    public function reply(Request $request, MessageThread $thread): RedirectResponse
    {
        Gate::authorize('access', $thread->band);

        $validated = $request->validate([
            'body' => ['required', 'string', 'max:'.Message::MAX_LENGTH],
        ], [
            'body.required' => 'Die Nachricht ist leer.',
            'body.max' => 'Die Nachricht ist zu lang (max. '.Message::MAX_LENGTH.' Zeichen).',
        ]);

        app(SendMessageAction::class)->execute($thread, $request->user(), $validated['body']);

        app(RecordActivityAction::class)->execute('message.sent', ['thread_id' => $thread->id, 'band_id' => $thread->band_id]);

        return redirect(route('band-manager.messages.show', $thread).'#antwort')->with('success', 'Antwort wurde gesendet.');
    }

    /**
     * Ganze Unterhaltung: Die Agentur löscht für alle, Bands blenden sie für sich aus.
     */
    public function destroy(Request $request, MessageThread $thread): RedirectResponse
    {
        Gate::authorize('access', $thread->band);

        if ($request->user()->can('messages.manage')) {
            $thread->delete();   // Nachrichten + Gelesen-Status löscht die Datenbank mit
            app(RecordActivityAction::class)->execute('message.thread_deleted', ['thread_id' => $thread->id, 'band_id' => $thread->band_id]);

            return to_route('band-manager.messages.index')->with('success', 'Die Unterhaltung wurde für alle gelöscht.');
        }

        if ($this->impersonation->isActive()) {
            return to_route('band-manager.messages.show', $thread)
                ->with('error', 'Beim „Einloggen als“ kannst du keine Unterhaltungen für den Künstler ausblenden.');
        }

        app(HideThreadAction::class)->execute($thread, $request->user());
        app(RecordActivityAction::class)->execute('message.thread_hidden', ['thread_id' => $thread->id, 'band_id' => $thread->band_id]);

        return to_route('band-manager.messages.index')
            ->with('success', 'Die Unterhaltung wurde aus deiner Liste entfernt. Bei einer neuen Nachricht erscheint sie wieder.');
    }

    /**
     * Einzelne Nachricht: eigene immer, fremde nur die Agentur.
     */
    public function destroyMessage(Request $request, MessageThread $thread, Message $message): RedirectResponse
    {
        Gate::authorize('access', $thread->band);
        abort_unless($message->isFrom($request->user()) || $request->user()->can('messages.manage'), 403);

        $message->delete();

        app(RecordActivityAction::class)->execute('message.deleted', ['thread_id' => $thread->id, 'message_id' => $message->id, 'band_id' => $thread->band_id]);

        if (! $thread->messages()->exists()) {
            $thread->delete();

            return to_route('band-manager.messages.index')->with('success', 'Nachricht gelöscht. Die Unterhaltung war danach leer und wurde entfernt.');
        }

        app(RefreshLastMessageAtAction::class)->execute($thread);

        return to_route('band-manager.messages.show', $thread)->with('success', 'Nachricht wurde gelöscht.');
    }

    private function bandFilter(Request $request): ?Band
    {
        if (! $request->filled('band')) {
            return null;
        }

        $band = Band::query()->findOrFail($request->integer('band'));
        Gate::authorize('access', $band);

        return $band;
    }
}
