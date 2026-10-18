<?php

namespace App\Services\BandManager;

use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Support\Str;

/**
 * HTML → PDF mit Dompdf: A4 hoch, DejaVu Sans für Umlaute, optionale Fußzeile mit Seitenzahl.
 */
class Pdf
{
    /**
     * $footer darf {PAGE_NUM} und {PAGE_COUNT} enthalten, $orientation ist "portrait" oder "landscape".
     */
    public function render(string $html, string $footer = '', string $orientation = 'portrait'): string
    {
        $options = new Options;
        $options->set('defaultFont', 'DejaVu Sans');
        $options->set('isRemoteEnabled', false);

        $pdf = new Dompdf($options);
        $pdf->loadHtml($html, 'UTF-8');
        $pdf->setPaper('A4', $orientation);
        $pdf->render();

        if ($footer !== '') {
            $font = $pdf->getFontMetrics()->getFont('DejaVu Sans');
            $canvas = $pdf->getCanvas();
            $canvas->page_text(40, $canvas->get_height() - 30, $footer, $font, 7, [0.42, 0.42, 0.47]);
        }

        return (string) $pdf->output();
    }

    /**
     * Agentur-Logo als eingebettetes Bild (Dompdf lädt keine externen Dateien).
     */
    public function logo(): ?string
    {
        $file = (string) config('band-manager.pdf_logo');

        if (! is_file($file)) {
            return null;
        }

        $mime = in_array(strtolower(pathinfo($file, PATHINFO_EXTENSION)), ['jpg', 'jpeg'], true) ? 'image/jpeg' : 'image/png';

        return 'data:'.$mime.';base64,'.base64_encode((string) file_get_contents($file));
    }

    /**
     * Teil eines Dateinamens: "Rüdiger & Co" → "Ruediger-Co".
     */
    public function fileSlug(string $text): string
    {
        $text = strtr($text, ['ä' => 'ae', 'ö' => 'oe', 'ü' => 'ue', 'Ä' => 'Ae', 'Ö' => 'Oe', 'Ü' => 'Ue', 'ß' => 'ss']);

        return trim((string) preg_replace('/[^A-Za-z0-9]+/', '-', Str::ascii($text)), '-');
    }
}
