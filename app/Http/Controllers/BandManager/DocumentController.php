<?php

namespace App\Http\Controllers\BandManager;

use App\Actions\BandManager\RecordActivityAction;
use App\Enums\BandManager\RiderCategoryEnum;
use App\Enums\MediaCollectionEnum;
use App\Http\Controllers\Controller;
use App\Http\Requests\BandManager\DocumentRequest;
use App\Models\BandManager\Band;
use App\Models\BandManager\Document;
use App\Models\BandManager\Gig;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Verträge (Agentur, optional mit einem Gig verknüpft) und Rider (Bands) als PDF.
 */
class DocumentController extends Controller
{
    /**
     * ?band=ID filtert nach Band, ?gig=ID wählt den Gig im Vertrags-Formular vor.
     */
    public function index(Request $request): View
    {
        $user = $request->user();
        $band = null;

        if ($request->filled('band')) {
            $band = Band::query()->findOrFail($request->integer('band'));
            Gate::authorize('access', $band);
        }

        $canManage = $user->can('documents.manage');

        return view('band-manager.documents.index', [
            'contracts' => $this->documents($request, Document::TYPE_CONTRACT, $band),
            'riders' => $this->documents($request, Document::TYPE_RIDER, $band),
            'bands' => Band::query()->accessibleBy($user)->orderBy('name')->get(),
            'bandId' => $band?->id,
            'gigId' => $request->integer('gig'),
            'canManage' => $canManage,
            'canUpload' => $canManage || $user->can('documents.upload'),
            'categories' => RiderCategoryEnum::cases(),
            'gigs' => $canManage ? $this->gigOptions($band) : collect(),
            'maxMb' => (int) config('band-manager.documents.max_size_mb'),
        ]);
    }

    public function store(DocumentRequest $request): RedirectResponse
    {
        $bandId = (int) $request->validated('band_id');

        $document = Document::query()->create([
            'band_id' => $bandId,
            'gig_id' => $request->validated('gig_id'),
            'type' => $request->validated('type'),
            'category' => $request->validated('category'),
            'title' => $request->title(),
            'uploaded_by' => $request->user()->id,
        ]);
        $document->addMediaFromRequest('file')->toMediaCollection(MediaCollectionEnum::Documents->value);

        app(RecordActivityAction::class)->execute('document.uploaded', ['document_id' => $document->id, 'band_id' => $bandId, 'type' => $document->type]);

        return $this->backToList($request)
            ->with('success', $document->isContract() ? 'Vertrag wurde hinterlegt.' : 'Rider wurde hochgeladen.');
    }

    /**
     * PDF im Browser öffnen – mit ?download=1 als Download.
     */
    public function show(Request $request, Document $document): StreamedResponse
    {
        Gate::authorize('view', $document);

        $media = $document->getFirstMedia(MediaCollectionEnum::Documents->value);
        abort_if($media === null, 404);

        return $request->boolean('download') ? $media->toResponse($request) : $media->toInlineResponse($request);
    }

    /**
     * Die Datei löscht das Model beim Löschen mit.
     */
    public function destroy(Request $request, Document $document): RedirectResponse
    {
        Gate::authorize('delete', $document);

        $document->delete();

        app(RecordActivityAction::class)->execute('document.deleted', ['document_id' => $document->id, 'band_id' => $document->band_id, 'type' => $document->type]);

        return $this->backToList($request)->with('success', 'Dokument wurde gelöscht.');
    }

    /**
     * @return Collection<int, Document>
     */
    private function documents(Request $request, string $type, ?Band $band): Collection
    {
        return Document::query()
            ->with(['band', 'gig', 'media'])
            ->where('type', $type)
            ->whereIn('band_id', Band::query()->accessibleBy($request->user())->select('id'))
            ->when($band, fn ($query) => $query->where('band_id', $band->id))
            ->latest()
            ->latest('id')
            ->get();
    }

    /**
     * Gigs für die Auswahl beim Vertrags-Upload (ab einem Jahr zurück).
     *
     * @return Collection<int, Gig>
     */
    private function gigOptions(?Band $band): Collection
    {
        return Gig::query()
            ->with('band')
            ->where('date', '>=', CarbonImmutable::today()->subYear()->format('Y-m-d'))
            ->when($band, fn ($query) => $query->where('band_id', $band->id))
            ->orderByDesc('date')
            ->get();
    }

    private function backToList(Request $request): RedirectResponse
    {
        return to_route('band-manager.documents.index', array_filter(['band' => $request->integer('filter') ?: null]));
    }
}
