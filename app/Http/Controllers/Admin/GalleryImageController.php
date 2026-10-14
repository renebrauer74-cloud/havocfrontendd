<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\GalleryImageRequest;
use App\Models\Artist;
use App\Models\GalleryImage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class GalleryImageController extends Controller
{
    /**
     * Galerie einer per ?artist=<id> ausgewählten Band.
     */
    public function index(Request $request): View
    {
        $selectedArtist = Artist::query()->find($request->integer('artist'));

        return view('admin.gallery', [
            'artists' => Artist::alphabetical(),
            'selectedArtist' => $selectedArtist,
            'photos' => $selectedArtist?->galleryImages ?? collect(),
        ]);
    }

    public function store(GalleryImageRequest $request): RedirectResponse
    {
        $image = new GalleryImage($request->safe()->only('artist_id'));
        $image->replaceImage($request->file('image'));
        $image->save();

        return to_route('admin.gallery.index', ['artist' => $image->artist_id])
            ->with('status', 'Galeriebild gespeichert //');
    }

    public function destroy(GalleryImage $galleryImage): RedirectResponse
    {
        $galleryImage->delete();

        return to_route('admin.gallery.index', ['artist' => $galleryImage->artist_id])
            ->with('status', 'Galeriebild gelöscht //');
    }
}
