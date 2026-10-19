<?php

namespace App\Http\Controllers\BandManager;

use App\Actions\BandManager\RecordActivityAction;
use App\Enums\BandManager\CloudLinkTypeEnum;
use App\Http\Controllers\Controller;
use App\Http\Requests\BandManager\CloudLinkRequest;
use App\Models\BandManager\Band;
use App\Models\BandManager\CloudFile;
use App\Models\BandManager\CloudFolder;
use App\Models\BandManager\CloudLink;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\View\View;

/**
 * Freigabe-Links einer Band: anlegen (für einen Ordner oder eine Datei), auflisten, widerrufen.
 */
class CloudLinkController extends Controller
{
    /**
     * Nur Links zum Ansehen – Upload-Links stehen unter Cloud → Photo Drop.
     */
    public function index(Request $request, Band $band): View
    {
        Gate::authorize('access', $band);

        return view('band-manager.cloud.links', [
            'band' => $band,
            'links' => $band->cloudLinks()->where('type', CloudLinkTypeEnum::Read)->with(['folder', 'file.media', 'creator'])->latest()->latest('id')->get(),
            'newLinkId' => (int) $request->session()->get('new_link_id'),
        ]);
    }

    public function storeForFolder(CloudLinkRequest $request, CloudFolder $folder): RedirectResponse
    {
        Gate::authorize('access', $folder->band);

        $type = $request->type();

        return $this->store($request, $folder->band, [
            'folder_id' => $folder->id,
            'type' => $type,
            'message' => $request->validated('message'),
            'allow_all_types' => $type->allowsUpload() && $request->boolean('allow_all_types'),
        ]);
    }

    /**
     * Einzelne Dateien gibt es nur zum Ansehen & Herunterladen.
     */
    public function storeForFile(CloudLinkRequest $request, CloudFile $file): RedirectResponse
    {
        Gate::authorize('access', $file->band);

        return $this->store($request, $file->band, [
            'file_id' => $file->id,
            'type' => CloudLinkTypeEnum::Read,
            'message' => $request->validated('message'),
        ]);
    }

    public function destroy(CloudLink $link): RedirectResponse
    {
        Gate::authorize('access', $link->band);

        $link->delete();

        app(RecordActivityAction::class)->execute('cloud.link_revoked', ['link_id' => $link->id, 'band_id' => $link->band_id]);

        return to_route('band-manager.cloud.links', $link->band_id)->with('success', 'Der Link wurde widerrufen und funktioniert nicht mehr.');
    }

    /**
     * @param  array{folder_id?: int, file_id?: int, type: CloudLinkTypeEnum, message: ?string, allow_all_types?: bool}  $attributes
     */
    private function store(CloudLinkRequest $request, Band $band, array $attributes): RedirectResponse
    {
        $link = CloudLink::query()->create([
            'band_id' => $band->id,
            ...$attributes,
            'token' => Str::random(40),
            'expires_at' => $request->expiresAt(),
            'created_by' => $request->user()->id,
        ]);

        app(RecordActivityAction::class)->execute('cloud.link_created', ['link_id' => $link->id, 'band_id' => $band->id]);

        return to_route('band-manager.cloud.links', $band)
            ->with('success', 'Der Link ist fertig – kopieren und weitergeben.')
            ->with('new_link_id', $link->id);
    }
}
