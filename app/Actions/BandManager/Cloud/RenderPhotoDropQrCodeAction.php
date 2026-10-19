<?php

namespace App\Actions\BandManager\Cloud;

use App\Models\BandManager\CloudLink;
use chillerlan\QRCode\Common\EccLevel;
use chillerlan\QRCode\Output\QRGdImagePNG;
use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;

/**
 * QR-Code (PNG) zur öffentlichen Upload-Seite eines Photo Drops – groß genug für Flyer und Leinwand.
 */
class RenderPhotoDropQrCodeAction
{
    public function execute(CloudLink $link): string
    {
        $options = new QROptions([
            'outputInterface' => QRGdImagePNG::class,
            'outputBase64' => false,
            'eccLevel' => EccLevel::M,
            'scale' => 20,
            'quietzoneSize' => 4,
        ]);

        return (new QRCode($options))->render(route('band-manager.share.show', $link));
    }
}
