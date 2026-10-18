<?php

namespace App\Enums;

enum MediaConversionEnum: string
{
    /** Verkleinertes JPEG für Web und PDF, das Original bleibt erhalten */
    case Preview = 'preview';
}
