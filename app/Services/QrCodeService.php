<?php

namespace App\Services;

use App\Models\AssessmentForm;
use BaconQrCode\Common\ErrorCorrectionLevel;
use BaconQrCode\Encoder\Encoder;

class QrCodeService
{
    private const SCALE = 10;

    private const QUIET_ZONE_MODULES = 4;

    public function signatureText(AssessmentForm $form): string
    {
        $dosenName = $form->dosen->name.($form->dosen->title ? ', '.$form->dosen->title : '');
        $jenisSidang = $form->submission?->schedule?->jenisSidang?->nama;
        $tanggal = optional($form->created_at)->locale('id')->translatedFormat('d F Y');

        return "Tanda Tangan Elektronik Penilaian Sidang\n"
            .'Dosen: '.$dosenName."\n"
            .($jenisSidang ? 'Jenis Sidang: '.$jenisSidang."\n" : '')
            .'Tanggal: '.$tanggal;
    }

    public function penilaianSignature(AssessmentForm $form): string
    {
        $matrix = Encoder::encode($this->signatureText($form), ErrorCorrectionLevel::M())->getMatrix();

        $modules = $matrix->getWidth();
        $margin = self::QUIET_ZONE_MODULES * self::SCALE;
        $pixel = ($modules + 2 * self::QUIET_ZONE_MODULES) * self::SCALE;

        $image = imagecreatetruecolor($pixel, $pixel);
        $white = imagecolorallocate($image, 255, 255, 255);
        $black = imagecolorallocate($image, 0, 0, 0);
        imagefill($image, 0, 0, $white);

        for ($y = 0; $y < $matrix->getHeight(); $y++) {
            for ($x = 0; $x < $modules; $x++) {
                if ($matrix->get($x, $y) === 1) {
                    imagefilledrectangle(
                        $image,
                        $margin + $x * self::SCALE,
                        $margin + $y * self::SCALE,
                        $margin + ($x + 1) * self::SCALE - 1,
                        $margin + ($y + 1) * self::SCALE - 1,
                        $black,
                    );
                }
            }
        }

        ob_start();
        imagepng($image);
        $png = (string) ob_get_clean();
        imagedestroy($image);

        return 'data:image/png;base64,'.base64_encode($png);
    }
}
