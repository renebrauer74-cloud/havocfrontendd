<?php

namespace App\Http\Controllers\BandManager;

use App\Http\Controllers\Controller;
use App\Models\BandManager\ActivityLog;
use App\Models\BandManager\Band;
use App\Models\BandManager\GalleryImage;
use App\Services\BandManager\Images;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Bildergalerie: bis zu 8 Fotos pro Band, ein Bandfoto ist das Hauptbild (Titelblatt im Pressekit).
 */
class GalleryController extends Controller
{
    /**
     * Band-Auswahl – Bands mit genau einer Band landen direkt in deren Galerie.
     */
    public function index(Request $request): View|RedirectResponse
    {
        $user = $request->user();
        $bands = $user->accessibleBands()
            ->withCount('galleryImages')
            ->with(['galleryImages' => fn ($query) => $query->where('is_main', true)])
            ->orderBy('name')
            ->get();

        if (! $user->isAdmin() && $bands->count() === 1) {
            return to_route('band-manager.gallery.band', $bands->first());
        }

        return view('band-manager.gallery.index', ['bands' => $bands, 'max' => GalleryImage::maxImages()]);
    }

    public function band(Request $request, Band $band, Images $images): View
    {
        Gate::authorize('access', $band);

        $galleryImages = $band->galleryImages()->get();

        return view('band-manager.gallery.band', [
            'band' => $band,
            'images' => $galleryImages,
            'bands' => $request->user()->accessibleBands()->orderBy('name')->get(),
            'hasMain' => $galleryImages->contains('is_main', true),
            'canEdit' => $request->user()->can('gallery.edit'),
            'max' => GalleryImage::maxImages(),
            'maxBytes' => GalleryImage::maxKilobytes() * 1024,
            'postMax' => $images->iniBytes((string) ini_get('post_max_size')),
            'categories' => GalleryImage::categories(),
        ]);
    }

    /**
     * Mehrere Bilder auf einmal – ungültige Dateien werden übersprungen und einzeln gemeldet.
     */
    public function store(Request $request, Band $band): RedirectResponse
    {
        Gate::authorize('access', $band);

        $validated = $request->validate([
            'photos' => ['required', 'array'],
            'category' => ['nullable', Rule::in(array_keys(GalleryImage::categories()))],
            'caption' => ['nullable', 'string', 'max:190'],
            'credit' => ['nullable', 'string', 'max:150'],
            'make_main' => ['boolean'],
        ], [
            'photos.required' => 'Bitte mindestens ein Bild auswählen.',
        ]);

        $back = to_route('band-manager.gallery.band', $band);
        $max = GalleryImage::maxImages();
        $free = $max - $band->galleryImages()->count();

        if ($free <= 0) {
            return $back->with('error', "Die Galerie ist voll (max. {$max} Bilder). Bitte zuerst ein Bild löschen.");
        }

        $nextSort = (int) $band->galleryImages()->max('sort_order') + 1;
        $uploaded = [];
        $errors = [];

        foreach ($request->file('photos', []) as $upload) {
            if (count($uploaded) >= $free) {
                $errors[] = "Maximal {$max} Bilder pro Band – nicht alle ausgewählten Bilder wurden hochgeladen.";
                break;
            }

            if ($error = $this->uploadError($upload)) {
                $errors[] = '„'.($upload instanceof UploadedFile ? $upload->getClientOriginalName() : 'Datei').'“: '.$error;

                continue;
            }

            $uploaded[] = GalleryImage::query()->create(GalleryImage::storeUpload($upload, $band->id) + [
                'band_id' => $band->id,
                'category' => $validated['category'] ?? GalleryImage::MAIN_CATEGORY,
                'caption' => $validated['caption'] ?? null,
                'credit' => $validated['credit'] ?? null,
                'is_main' => false,
                'sort_order' => $nextSort++,
                'uploaded_by' => $request->user()->id,
            ]);
        }

        if ($uploaded !== [] && $request->boolean('make_main')) {
            $uploaded[0]->makeMain();
        }

        if ($uploaded !== []) {
            ActivityLog::record('gallery.uploaded', ['band_id' => $band->id, 'count' => count($uploaded)]);
            $back->with('success', count($uploaded) === 1 ? '1 Bild wurde hochgeladen.' : count($uploaded).' Bilder wurden hochgeladen.');

            if (GalleryImage::mainFor($band) === null) {
                $back->with('warning', 'Es ist noch kein Hauptbild festgelegt. Bitte bei einem Bandfoto auf „Als Hauptbild“ klicken.');
            }
        }

        return $back->with('error', $errors);
    }

    /**
     * Bildunterschrift, Fotograf und Art ändern – das Hauptbild bleibt immer ein Bandfoto.
     */
    public function update(Request $request, GalleryImage $image): RedirectResponse
    {
        Gate::authorize('access', $image->band);

        $validated = $request->validate([
            'caption' => ['nullable', 'string', 'max:190'],
            'credit' => ['nullable', 'string', 'max:150'],
            'category' => ['nullable', Rule::in(array_keys(GalleryImage::categories()))],
        ]);

        $image->update([
            'caption' => $validated['caption'] ?? null,
            'credit' => $validated['credit'] ?? null,
            'category' => $image->is_main ? GalleryImage::MAIN_CATEGORY : ($validated['category'] ?? $image->category),
        ]);

        ActivityLog::record('gallery.updated', ['image_id' => $image->id, 'band_id' => $image->band_id]);

        return to_route('band-manager.gallery.band', $image->band_id)->with('success', 'Bild wurde gespeichert.');
    }

    public function makeMain(GalleryImage $image): RedirectResponse
    {
        Gate::authorize('access', $image->band);

        $image->makeMain();

        ActivityLog::record('gallery.main_set', ['image_id' => $image->id, 'band_id' => $image->band_id]);

        return to_route('band-manager.gallery.band', $image->band_id)->with('success', 'Hauptbild wurde festgelegt.');
    }

    /**
     * Die Dateien löscht das Model beim Löschen mit.
     */
    public function destroy(GalleryImage $image): RedirectResponse
    {
        Gate::authorize('access', $image->band);

        $image->delete();

        ActivityLog::record('gallery.deleted', ['image_id' => $image->id, 'band_id' => $image->band_id]);

        $response = to_route('band-manager.gallery.band', $image->band_id)->with('success', 'Bild wurde gelöscht.');

        return $image->is_main ? $response->with('warning', 'Das war das Hauptbild – bitte ein neues Hauptbild festlegen.') : $response;
    }

    /**
     * Bild ausliefern: Vorschau (Standard) oder ?size=original, mit ?download=1 als Download des Originals.
     */
    public function image(Request $request, GalleryImage $image): StreamedResponse
    {
        Gate::authorize('access', $image->band);

        $original = $request->query('size') === 'original' || $request->boolean('download');
        $path = $image->storagePath(! $original);
        $disk = Storage::disk('band-manager');
        abort_unless($disk->exists($path), 404);

        $headers = [
            'Content-Type' => $image->isPreviewPath($path) ? 'image/jpeg' : $image->mime,
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'private, max-age=86400',
        ];

        if ($request->boolean('download')) {
            return $disk->download($path, $image->original_name ?: basename($path), $headers);
        }

        return $disk->response($path, null, $headers);
    }

    private function uploadError(mixed $upload): ?string
    {
        $maxMb = (int) floor(GalleryImage::maxKilobytes() / 1024);

        if (! $upload instanceof UploadedFile) {
            return 'Upload fehlgeschlagen – bitte erneut versuchen.';
        }

        if (in_array($upload->getError(), [UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE], true)) {
            return "zu groß (max. {$maxMb} MB).";
        }

        if ($upload->getError() === UPLOAD_ERR_PARTIAL) {
            return 'Upload wurde unterbrochen (Verbindung zu langsam?) – bitte erneut versuchen.';
        }

        $validator = Validator::make(['photo' => $upload], [
            'photo' => ['file', 'max:'.GalleryImage::maxKilobytes(), 'mimetypes:'.implode(',', array_keys(GalleryImage::TYPES)), 'image'],
        ]);

        if ($validator->errors()->has('photo')) {
            return match (array_key_first($validator->failed()['photo'])) {
                'Max' => "zu groß (max. {$maxMb} MB).",
                'File', 'Uploaded' => 'Upload fehlgeschlagen – bitte erneut versuchen.',
                default => 'nur JPG-, PNG- oder WebP-Bilder sind erlaubt.',
            };
        }

        return null;
    }
}
