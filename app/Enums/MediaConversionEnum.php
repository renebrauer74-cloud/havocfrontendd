<?php

namespace App\Enums;

enum MediaConversionEnum: string
{
    /** Verkleinertes JPEG für Web und PDF, das Original bleibt erhalten */
    case Preview = 'preview';

    /** Verkleinertes PNG (mit Transparenz) für PDFs – z. B. das Bandlogo im Tech Rider */
    case PdfLogo = 'pdf-logo';

    /** Quadratisches JPEG für die Dateiliste im Cloud-Speicher */
    case Thumbnail = 'thumbnail';
}
