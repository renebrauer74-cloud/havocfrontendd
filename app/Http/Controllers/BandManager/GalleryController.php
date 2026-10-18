<?php

namespace App\Http\Controllers\BandManager;

use App\Actions\BandManager\Gallery\MakeGalleryImageMainAction;
use App\Actions\BandManager\RecordActivityAction;
use App\Enums\BandManager\GalleryCategoryEnum;
use App\Enums\MediaCollectionEnum;
use App\Enums\MediaConversionEnum;
use App\Http\Controllers\Controller;
use App\Models\BandManager\Band;
use App\Models\BandManager\GalleryImage;
use App\Rules\MaxMegapixelsRule;
use App\Services\BandManager\Images;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Gate;
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
        $bands = Band::query()->accessibleBy($user)
            ->withCount('galleryImages')
            ->with(['galleryImages' => fn ($query) => $query->where('is_main', true)->with('media')])
            ->orderBy('name')
            ->get();

        if (! $user->isAdmin() && $bands->count() === 1) {
            return to_route('band-manager.gallery.band', $bands->first());
        }

        return view('band-manager.gallery.index', ['bands' => $bands, 'max' => (int) config('band-manager.gallery.max_images')]);
    }

    public function band(Request $request, Band $band, Images $images): View
    {
        Gate::authorize('access', $band);

        $galleryImages = $band->galleryImages()->with('media')->ordered()->get();

        return view('band-manager.gallery.band', [
            'band' => $band,
            'images' => $galleryImages,
            'bands' => Band::query()->accessibleBy($request->user())->orderBy('name')->get(),
            'hasMain' => $galleryImages->contains('is_main', true),
            'canEdit' => $request->user()->can('gallery.edit'),
            'max' => (int) config('band-manager.gallery.max_images'),
            'maxBytes' => $this->maxKilobytes($images) * 1024,
            'postMax' => $images->iniBytes((string) ini_get('post_max_size')),
            'categories' => GalleryCategoryEnum::cases(),
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
            'category' => ['nullable', Rule::enum(GalleryCategoryEnum::class)],
            'caption' => ['nullable', 'string', 'max:190'],
            'credit' => ['nullable', 'string', 'max:150'],
            'make_main' => ['boolean'],
        ], [
            'photos.required' => 'Bitte mindestens ein Bild auswählen.',
        ]);

        $back = to_route('band-manager.gallery.band', $band);
        $max = (int) config('band-manager.gallery.max_images');
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

            [$width, $height] = getimagesize((string) $upload->getRealPath());

            $image = GalleryImage::query()->create([
                'band_id' => $band->id,
                'category' => $validated['category'] ?? GalleryCategoryEnum::Band,
                'caption' => $validated['caption'] ?? null,
                'credit' => $validated['credit'] ?? null,
                'is_main' => false,
                'sort_order' => $nextSort++,
                'uploaded_by' => $request->user()->id,
            ]);
            $image->addMedia($upload)
                ->withCustomProperties(['width' => $width, 'height' => $height])
                ->toMediaCollection(MediaCollectionEnum::BandPhotos->value);
            $uploaded[] = $image;
        }

        if ($uploaded !== [] && $request->boolean('make_main')) {
            app(MakeGalleryImageMainAction::class)->execute($uploaded[0]);
        }

        if ($uploaded !== []) {
            app(RecordActivityAction::class)->execute('gallery.uploaded', ['band_id' => $band->id, 'count' => count($uploaded)]);
            $back->with('success', count($uploaded) === 1 ? '1 Bild wurde hochgeladen.' : count($uploaded).' Bilder wurden hochgeladen.');

            if (! $band->mainGalleryImage()->exists()) {
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
            'category' => ['nullable', Rule::enum(GalleryCategoryEnum::class)],
        ]);

        $image->update([
            'caption' => $validated['caption'] ?? null,
            'credit' => $validated['credit'] ?? null,
            'category' => $image->is_main ? GalleryCategoryEnum::Band : ($validated['category'] ?? $image->category),
        ]);

        app(RecordActivityAction::class)->execute('gallery.updated', ['image_id' => $image->id, 'band_id' => $image->band_id]);

        return to_route('band-manager.gallery.band', $image->band_id)->with('success', 'Bild wurde gespeichert.');
    }

    public function makeMain(GalleryImage $image): RedirectResponse
    {
        Gate::authorize('access', $image->band);

        app(MakeGalleryImageMainAction::class)->execute($image);

        app(RecordActivityAction::class)->execute('gallery.main_set', ['image_id' => $image->id, 'band_id' => $image->band_id]);

        return to_route('band-manager.gallery.band', $image->band_id)->with('success', 'Hauptbild wurde festgelegt.');
    }

    /**
     * Die Dateien löscht das Model beim Löschen mit.
     */
    public function destroy(GalleryImage $image): RedirectResponse
    {
        Gate::authorize('access', $image->band);

        $image->delete();

        app(RecordActivityAction::class)->execute('gallery.deleted', ['image_id' => $image->id, 'band_id' => $image->band_id]);

        $response = to_route('band-manager.gallery.band', $image->band_id)->with('success', 'Bild wurde gelöscht.');

        return $image->is_main ? $response->with('warning', 'Das war das Hauptbild – bitte ein neues Hauptbild festlegen.') : $response;
    }

    /**
     * Bild ausliefern: Vorschau (Standard) oder ?size=original, mit ?download=1 als Download des Originals.
     */
    public function image(Request $request, GalleryImage $image): StreamedResponse
    {
        Gate::authorize('access', $image->band);

        $media = $image->getFirstMedia(MediaCollectionEnum::BandPhotos->value);
        abort_if($media === null, 404);

        if ($request->boolean('download')) {
            return $media->toResponse($request);
        }

        return $request->query('size') === 'original' || ! $media->hasGeneratedConversion(MediaConversionEnum::Preview->value)
            ? $media->toInlineResponse($request)
            : $media->toInlineResponse($request, MediaConversionEnum::Preview->value);
    }

    private function uploadError(mixed $upload): ?string
    {
        $maxMb = (int) floor($this->maxKilobytes(app(Images::class)) / 1024);

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
            'photo' => [
                'file', 'max:'.$this->maxKilobytes(app(Images::class)), 'mimetypes:'.implode(',', array_keys(GalleryImage::TYPES)), 'image',
                new MaxMegapixelsRule((int) config('band-manager.gallery.max_megapixels')),
            ],
        ]);

        if ($validator->errors()->has('photo')) {
            return match (array_key_first($validator->failed()['photo'])) {
                'Max' => "zu groß (max. {$maxMb} MB).",
                'File', 'Uploaded' => 'Upload fehlgeschlagen – bitte erneut versuchen.',
                MaxMegapixelsRule::class => $validator->errors()->first('photo'),
                default => 'nur JPG-, PNG- oder WebP-Bilder sind erlaubt.',
            };
        }

        return null;
    }

    /**
     * Max. Dateigröße pro Bild in KB – berücksichtigt auch upload_max_filesize des Servers.
     */
    private function maxKilobytes(Images $images): int
    {
        $own = (int) config('band-manager.gallery.max_photo_mb') * 1024;
        $ini = (int) floor($images->iniBytes((string) ini_get('upload_max_filesize')) / 1024);

        return $ini > 0 ? min($own, $ini) : $own;
    }
}
