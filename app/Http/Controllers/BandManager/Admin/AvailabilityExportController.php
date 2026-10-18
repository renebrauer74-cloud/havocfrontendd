<?php

namespace App\Http\Controllers\BandManager\Admin;

use App\Actions\BandManager\Availability\DescribeAvailabilityTimeAction;
use App\Actions\BandManager\RecordActivityAction;
use App\Http\Controllers\Controller;
use App\Models\BandManager\Availability;
use App\Models\BandManager\Band;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Verfügbarkeiten eines Monats als CSV (Excel-kompatibel: Semikolon, UTF-8 mit BOM).
 */
class AvailabilityExportController extends Controller
{
    public function index(): View
    {
        return view('band-manager.admin.availability-export', [
            'bands' => Band::query()->orderBy('name')->get(),
            'month' => now()->format('Y-m'),
        ]);
    }

    public function download(Request $request, DescribeAvailabilityTimeAction $describeTime): StreamedResponse
    {
        $month = preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', (string) $request->query('month')) ? $request->query('month') : now()->format('Y-m');
        $bandId = $request->integer('band') ?: null;
        $from = CarbonImmutable::createFromFormat('!Y-m', $month);

        $rows = Availability::query()
            ->join('bm_bands', 'bm_bands.id', '=', 'bm_availabilities.band_id')
            ->whereBetween('bm_availabilities.date', [$from->format('Y-m-d'), $from->endOfMonth()->format('Y-m-d')])
            ->when($bandId, fn ($query) => $query->where('bm_availabilities.band_id', $bandId))
            ->orderBy('bm_bands.name')
            ->orderBy('bm_availabilities.date')
            ->get([
                'bm_bands.name AS band_name', 'bm_availabilities.date', 'bm_availabilities.status', 'bm_availabilities.title',
                'bm_availabilities.start_time', 'bm_availabilities.end_time', 'bm_availabilities.note',
            ]);

        app(RecordActivityAction::class)->execute('export.csv', ['month' => $month, 'band_id' => $bandId]);

        return response()->streamDownload(function () use ($rows, $describeTime): void {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF"); // BOM für Excel
            fputcsv($out, ['Band', 'Datum', 'Status', 'Titel', 'Uhrzeit', 'Notiz'], ';', escape: '');

            foreach ($rows as $row) {
                fputcsv($out, [
                    $row->band_name,
                    CarbonImmutable::parse($row->date)->format('d.m.Y'),
                    $row->status->label(),
                    $row->title,
                    $describeTime->execute($row) ?? 'ganztägig',
                    $row->note,
                ], ';', escape: '');
            }

            fclose($out);
        }, "verfuegbarkeiten-{$month}.csv", ['Content-Type' => 'text/csv; charset=utf-8']);
    }
}
