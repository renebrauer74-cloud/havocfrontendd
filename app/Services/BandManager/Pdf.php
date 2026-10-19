<?php

namespace App\Services\BandManager;

use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Support\Str;
use setasign\Fpdi\Fpdi;
use setasign\Fpdi\FpdiException;
use setasign\Fpdi\PdfParser\StreamReader;

/**
 * HTML → PDF mit Dompdf: A4 hoch, DejaVu Sans für Umlaute, optionale Fußzeile mit Seitenzahl.
 * Fertige PDFs (z. B. Location Sheets) lassen sich mit FPDI hinten anhängen.
 */
class Pdf
{
    /**
     * $footer darf {PAGE_NUM} und {PAGE_COUNT} enthalten, $orientation ist "portrait" oder "landscape".
     * $appendedPages zählt bei {PAGE_COUNT} die Seiten mit, die danach per append() angehängt werden.
     */
    public function render(string $html, string $footer = '', string $orientation = 'portrait', int $appendedPages = 0): string
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

            if ($appendedPages > 0) {
                $footer = str_replace('{PAGE_COUNT}', (string) ($canvas->get_page_count() + $appendedPages), $footer);
            }

            $canvas->page_text(40, $canvas->get_height() - 30, $footer, $font, 7, [0.42, 0.42, 0.47]);
        }

        return (string) $pdf->output();
    }

    /**
     * Seitenzahl eines PDFs – null, wenn FPDI es nicht lesen kann (z. B. verschlüsselt oder beschädigt).
     */
    public function pageCount(string $pdf): ?int
    {
        try {
            return (new Fpdi)->setSourceFile(StreamReader::createByString($pdf));
        } catch (FpdiException) {
            return null;
        }
    }

    /**
     * Hängt PDFs Seite für Seite an $pdf an – jede Seite behält ihr Format, Links bleiben klickbar.
     * Nicht lesbare PDFs werden übersprungen (siehe pageCount()).
     *
     * @param  list<string>  $attachments
     */
    public function append(string $pdf, array $attachments): string
    {
        if ($attachments === []) {
            return $pdf;
        }

        $merged = new Fpdi;

        foreach ([$pdf, ...$attachments] as $document) {
            try {
                $pages = $merged->setSourceFile(StreamReader::createByString($document));

                for ($page = 1; $page <= $pages; $page++) {
                    $template = $merged->importPage($page, importExternalLinks: true);
                    $size = $merged->getTemplateSize($template);
                    $merged->AddPage($size['orientation'], [$size['width'], $size['height']]);
                    $merged->useTemplate($template);
                }
            } catch (FpdiException) {
                continue;
            }
        }

        return $merged->Output('S');
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
