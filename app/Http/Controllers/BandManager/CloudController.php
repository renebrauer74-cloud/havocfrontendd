<?php

namespace App\Http\Controllers\BandManager;

use App\Actions\BandManager\Cloud\CalculateCloudUsageAction;
use App\Actions\BandManager\Cloud\CreateDefaultCloudFoldersAction;
use App\Actions\BandManager\Cloud\ListCloudFolderPathAction;
use App\Actions\BandManager\Cloud\ListCloudFolderTreeAction;
use App\Actions\BandManager\Cloud\SummarizeCloudFoldersAction;
use App\Http\Controllers\Controller;
use App\Models\BandManager\Band;
use App\Models\BandManager\CloudFile;
use App\Models\BandManager\CloudFolder;
use App\Services\BandManager\Images;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

/**
 * Cloud-Speicher: Band-Auswahl und Ordneransicht (oberste Ebene bzw. ein Ordner).
 */
class CloudController extends Controller
{
    /**
     * Band-Auswahl mit belegtem Speicher – Logins mit genau einer Band landen direkt in deren Cloud.
     */
    public function index(Request $request, CalculateCloudUsageAction $calculateUsage): View|RedirectResponse
    {
        $user = $request->user();
        $bands = Band::query()->accessibleBy($user)->orderBy('name')->get();

        if (! $user->isAdmin() && $bands->count() === 1) {
            return to_route('band-manager.cloud.band', $bands->first());
        }

        return view('band-manager.cloud.index', [
            'bands' => $bands,
            'usages' => $bands->mapWithKeys(fn (Band $band): array => [$band->id => $calculateUsage->execute($band)]),
        ]);
    }

    public function band(Request $request, Band $band): View
    {
        return $this->folderView($request, $band, null);
    }

    public function folder(Request $request, CloudFolder $folder): View
    {
        return $this->folderView($request, $folder->band, $folder);
    }

    private function folderView(Request $request, Band $band, ?CloudFolder $folder): View
    {
        Gate::authorize('access', $band);

        app(CreateDefaultCloudFoldersAction::class)->execute($band);

        $canEdit = $request->user()->can('cloud.edit');

        return view('band-manager.cloud.folder', [
            'band' => $band,
            'folder' => $folder,
            'path' => $folder ? app(ListCloudFolderPathAction::class)->execute($folder) : collect(),
            'folders' => CloudFolder::query()
                ->where('band_id', $band->id)
                ->where('parent_id', $folder?->id)
                ->ordered()
                ->get(),
            'summary' => app(SummarizeCloudFoldersAction::class)->execute($band),
            'files' => CloudFile::query()
                ->where('band_id', $band->id)
                ->where('folder_id', $folder?->id)
                ->with(['media', 'uploader'])
                ->latest()
                ->latest('id')
                ->get(),
            'usage' => app(CalculateCloudUsageAction::class)->execute($band),
            'bands' => Band::query()->accessibleBy($request->user())->orderBy('name')->get(),
            'canEdit' => $canEdit,
            'tree' => $canEdit ? app(ListCloudFolderTreeAction::class)->execute($band) : [],
            'maxBytes' => app(Images::class)->maxUploadKilobytes((int) config('band-manager.cloud.max_file_mb')) * 1024,
        ]);
    }
}
