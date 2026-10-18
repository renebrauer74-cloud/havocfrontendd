<?php

namespace App\Http\Controllers\BandManager;

use App\Actions\BandManager\RecordActivityAction;
use App\Http\Controllers\Controller;
use App\Models\BandManager\Band;
use App\Services\BandManager\Presskit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use RuntimeException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Pressekit zusammenstellen und als ZIP herunterladen.
 */
class PresskitController extends Controller
{
    public function __construct(private Presskit $presskit) {}

    /**
     * ?band=ID wählt die Band – bei nur einer Band automatisch.
     */
    public function create(Request $request): View
    {
        $bands = Band::query()->accessibleBy($request->user())->orderBy('name')->get();
        $band = null;

        if ($request->filled('band')) {
            $band = Band::query()->findOrFail($request->integer('band'));
            Gate::authorize('access', $band);
        } elseif ($bands->count() === 1) {
            $band = $bands->first();
        }

        return view('band-manager.presskit.create', [
            'bands' => $bands,
            'band' => $band,
            'inventory' => $band ? $this->presskit->inventory($band) : null,
            'missing' => $this->presskit->requirements(),
            'rootName' => $band ? 'Pressekit_'.$this->presskit->slug($band->name).'_'.now()->format('Y-m-d') : '',
        ]);
    }

    public function store(Request $request): BinaryFileResponse|RedirectResponse
    {
        $band = Band::query()->findOrFail($request->integer('band_id'));
        Gate::authorize('access', $band);

        $back = to_route('band-manager.presskit.create', ['band' => $band->id]);

        if ($missing = $this->presskit->requirements()) {
            return $back->with('error', $missing);
        }

        $options = $this->presskit->options($request->all());

        if ($error = $this->presskit->validationError($options)) {
            return $back->with('error', $error);
        }

        try {
            $zip = $this->presskit->build($band, $options);
        } catch (RuntimeException $exception) {
            return $back->with('error', $exception->getMessage());
        }

        app(RecordActivityAction::class)->execute('presskit.created', ['band_id' => $band->id, 'files' => $zip['count']]);

        return response()
            ->download($zip['path'], $zip['name'], [
                'Content-Type' => 'application/zip',
                'X-Content-Type-Options' => 'nosniff',
                'Cache-Control' => 'private, no-store',
            ])
            ->deleteFileAfterSend();
    }
}
