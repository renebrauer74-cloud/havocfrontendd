<?php

namespace App\Http\Controllers\BandManager;

use App\Actions\BandManager\Cloud\CheckCloudLinkAccessAction;
use App\Actions\BandManager\Cloud\CreateDefaultCloudFoldersAction;
use App\Actions\BandManager\Cloud\CreatePhotoDropAction;
use App\Actions\BandManager\Cloud\ListCloudFolderTreeAction;
use App\Actions\BandManager\Cloud\RenderPhotoDropQrCodeAction;
use App\Actions\BandManager\RecordActivityAction;
use App\Enums\BandManager\CloudLinkTypeEnum;
use App\Http\Controllers\Controller;
use App\Http\Requests\BandManager\PhotoDropRequest;
use App\Models\BandManager\Band;
use App\Models\BandManager\CloudFolder;
use App\Models\BandManager\CloudLink;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\HeaderUtils;

/**
 * Cloud → Photo Drop: Ordner mit Upload-Link anlegen, Link kopieren, teilen oder als QR-Code laden, Photo Drop beenden.
 * Die öffentliche Upload-Seite ist die Freigabe-Seite (CloudShareController, CloudShareUploadController).
 */
class PhotoDropController extends Controller
{
    /**
     * Band-Auswahl – Logins mit genau einer Band landen direkt in deren Photo Drops.
     */
    public function index(Request $request): View|RedirectResponse
    {
        $user = $request->user();
        $bands = Band::query()->accessibleBy($user)
            ->withCount(['cloudLinks as photo_drops_count' => fn ($query) => $query->where('type', CloudLinkTypeEnum::Upload)])
            ->orderBy('name')
            ->get();

        if (! $user->isAdmin() && $bands->count() === 1) {
            return to_route('band-manager.photo-drops.band', $bands->first());
        }

        return view('band-manager.photo-drop.index', ['bands' => $bands]);
    }

    public function band(Request $request, Band $band, CreateDefaultCloudFoldersAction $createDefaultFolders, ListCloudFolderTreeAction $listTree): View
    {
        Gate::authorize('access', $band);

        $createDefaultFolders->execute($band);

        return view('band-manager.photo-drop.band', [
            'band' => $band,
            'bands' => Band::query()->accessibleBy($request->user())->orderBy('name')->get(),
            'drops' => $band->cloudLinks()
                ->where('type', CloudLinkTypeEnum::Upload)
                ->with(['folder', 'creator'])
                ->withCount('uploads')
                ->latest()
                ->latest('id')
                ->get(),
            'tree' => $listTree->execute($band),
            'defaultParentId' => CloudFolder::query()
                ->where('band_id', $band->id)
                ->whereNull('parent_id')
                ->where('name', config('band-manager.cloud.photo_drop_folder'))
                ->value('id'),
            'newLinkId' => (int) $request->session()->get('new_link_id'),
        ]);
    }

    public function store(PhotoDropRequest $request, Band $band, CreatePhotoDropAction $createPhotoDrop): RedirectResponse
    {
        Gate::authorize('access', $band);

        $link = $createPhotoDrop->execute(
            $band,
            $request->parentFolder(),
            $request->validated('name'),
            $request->validated('message'),
            $request->expiresAt(),
            $request->boolean('allow_all_types'),
            $request->user(),
        );

        app(RecordActivityAction::class)->execute('cloud.folder_created', ['folder_id' => $link->folder_id, 'band_id' => $band->id]);
        app(RecordActivityAction::class)->execute('cloud.link_created', ['link_id' => $link->id, 'band_id' => $band->id]);

        return to_route('band-manager.photo-drops.band', $band)
            ->with('success', "Photo Drop „{$request->validated('name')}“ ist bereit – Link kopieren und teilen.")
            ->with('new_link_id', $link->id);
    }

    /**
     * QR-Code zur Upload-Seite – als Vorschau im Browser oder mit ?download=1 als PNG-Datei.
     */
    public function qrCode(Request $request, CloudLink $link, CheckCloudLinkAccessAction $checkAccess, RenderPhotoDropQrCodeAction $renderQrCode): Response
    {
        abort_unless($link->type->allowsUpload(), 404);
        Gate::authorize('access', $link->band);
        abort_unless($checkAccess->execute($link), 404);

        $headers = ['Content-Type' => 'image/png'];

        if ($request->boolean('download')) {
            $fileName = 'photo-drop-'.(Str::slug((string) $link->folder?->name, '-', 'de') ?: $link->id).'.png';
            $headers['Content-Disposition'] = HeaderUtils::makeDisposition(HeaderUtils::DISPOSITION_ATTACHMENT, $fileName);
        }

        return response($renderQrCode->execute($link), 200, $headers);
    }

    /**
     * Beenden = Link widerrufen. Ordner und hochgeladene Fotos bleiben im Speicher.
     */
    public function destroy(CloudLink $link): RedirectResponse
    {
        abort_unless($link->type->allowsUpload(), 404);
        Gate::authorize('access', $link->band);

        $link->delete();

        app(RecordActivityAction::class)->execute('cloud.link_revoked', ['link_id' => $link->id, 'band_id' => $link->band_id]);

        return to_route('band-manager.photo-drops.band', $link->band_id)
            ->with('success', 'Photo Drop beendet – der Link funktioniert nicht mehr, die Fotos bleiben im Speicher.');
    }
}
