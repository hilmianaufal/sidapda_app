<?php

namespace App\Http\Controllers;

use App\Models\Student;
use Barryvdh\DomPDF\Facade\Pdf;
use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Writer\PngWriter;

class StudentQrController extends Controller
{
    // Preview: PNG (aman, pakai GD)
    public function show(Student $student)
    {
        $builder = new Builder(
            writer: new PngWriter(),
            writerOptions: [],
            validateResult: false,
            data: $student->qr_token,
            size: 300,
            margin: 10
        );

        $result = $builder->build();

        return response($result->getString(), 200)
            ->header('Content-Type', $result->getMimeType()); // image/png
    }

    // Download: JPG (generate PNG dulu, lalu convert ke JPG pakai GD)
    public function download(Student $student)
    {
        $builder = new Builder(
            writer: new PngWriter(),
            writerOptions: [],
            validateResult: false,
            data: $student->qr_token,
            size: 800,
            margin: 20
        );

        $result = $builder->build();
        $pngBinary = $result->getString(); // PNG bytes

        // Convert PNG bytes -> JPG bytes (GD)
        $img = imagecreatefromstring($pngBinary);
        if ($img === false) {
            abort(500, 'Gagal membuat image dari PNG. Pastikan extension GD aktif.');
        }

        // Biar JPG background putih (PNG transparan kadang jadi hitam)
        $canvas = imagecreatetruecolor(imagesx($img), imagesy($img));
        $white = imagecolorallocate($canvas, 255, 255, 255);
        imagefill($canvas, 0, 0, $white);
        imagecopy($canvas, $img, 0, 0, 0, 0, imagesx($img), imagesy($img));

        ob_start();
        imagejpeg($canvas, null, 90);
        $jpgBinary = ob_get_clean();

        imagedestroy($img);
        imagedestroy($canvas);

        $filename = 'QR-' . $student->nis . '.jpg';

        return response($jpgBinary, 200)
            ->header('Content-Type', 'image/jpeg')
            ->header('Content-Disposition', 'attachment; filename="'.$filename.'"');
    }

        public function idCard(Student $student)
        {
            $pdf = Pdf::loadView('students.id_card_pdf', compact('student'))
                ->setPaper([0, 0, 300, 480], 'portrait');

            return $pdf->download('ID-Card-'.$student->nis.'.pdf');
        }

        public function idCardPng(Student $student)
        {
            $width = 900;
            $height = 1350;

            $img = imagecreatetruecolor($width, $height);
            imagealphablending($img, true);
            imagesavealpha($img, true);

            $greenDark = imagecolorallocate($img, 4, 78, 59);
            $green = imagecolorallocate($img, 5, 150, 105);
            $lime = imagecolorallocate($img, 132, 204, 22);
            $white = imagecolorallocate($img, 255, 255, 255);
            $softWhite = imagecolorallocatealpha($img, 255, 255, 255, 35);
            $textSoft = imagecolorallocate($img, 209, 250, 229);
            $slate = imagecolorallocate($img, 15, 23, 42);
            $gray = imagecolorallocate($img, 100, 116, 139);

            imagefill($img, 0, 0, $greenDark);

            for ($y = 0; $y < $height; $y++) {
                $ratio = $y / $height;

                $r = (int) (4 + (5 - 4) * $ratio);
                $g = (int) (78 + (150 - 78) * $ratio);
                $b = (int) (59 + (105 - 59) * $ratio);

                $color = imagecolorallocate($img, $r, $g, $b);
                imageline($img, 0, $y, $width, $y, $color);
            }

            imagefilledellipse($img, 820, 90, 520, 520, $lime);
            imagefilledellipse($img, 20, 1120, 520, 520, $green);
            imagefilledrectangle($img, 24, 24, 44, $height - 24, imagecolorallocatealpha($img, 255, 255, 255, 85));

            $fontBold = public_path('fonts/arialbd.ttf');
            $fontRegular = public_path('fonts/arial.ttf');

            if (!file_exists($fontBold) || !file_exists($fontRegular)) {
                abort(500, 'Font belum tersedia. Simpan arial.ttf dan arialbd.ttf di public/fonts.');
            }

            $logoPath = public_path('images/logo.png.png');
            $photoPath = $student->photo && file_exists(public_path($student->photo))
                ? public_path($student->photo)
                : public_path('images/default.jpg');

            // Header text
            imagettftext($img, 24, 0, 70, 95, $textSoft, $fontBold, 'S T U D E N T   I D');
            imagettftext($img, 52, 0, 70, 160, $white, $fontBold, 'SIDAPDA');
            imagettftext($img, 24, 0, 70, 215, $textSoft, $fontBold, 'Pondok Pesantren Darussalam');

            // Logo
            $this->drawRoundedRect($img, 680, 55, 830, 205, 34, $white);
            $this->drawImageContain($img, $logoPath, 705, 80, 100, 100);

            // Photo
            $this->drawRoundedRect($img, 270, 295, 630, 655, 60, $white);
            $this->drawRoundedRect($img, 285, 310, 615, 640, 48, imagecolorallocate($img, 220, 252, 231));
            $this->drawImageCover($img, $photoPath, 305, 330, 290, 290);

            // Identity
           $name = mb_strtoupper($student->name);

            $this->centerTextAutoSize(
                $img,
                $name,
                52,
                30,
                735,
                $white,
                $fontBold,
                $width,
                80
            );

            $this->centerText($img, 'NIS ' . $student->nis, 28, 795, $textSoft, $fontBold, $width);

            // Meta boxes
            $boxColor = imagecolorallocatealpha($img, 255, 255, 255, 80);
            $this->drawRoundedRect($img, 120, 855, 420, 995, 28, $boxColor);
            $this->drawRoundedRect($img, 480, 855, 780, 995, 28, $boxColor);

            $this->centerTextInBox($img, 'JENJANG', 20, 120, 885, 420, $textSoft, $fontBold);
            $this->centerTextInBox($img, $student->kelas ?: '-', 30, 120, 940, 420, $white, $fontBold);

            $this->centerTextInBox($img, 'KAMAR', 20, 480, 885, 780, $textSoft, $fontBold);
            $this->centerTextInBox($img, $student->kamar ?: '-', 30, 480, 940, 780, $white, $fontBold);

            // QR card
            // QR card
$this->drawRoundedRect($img, 90, 1040, 810, 1285, 36, $white);

$builder = new Builder(
    writer: new PngWriter(),
    writerOptions: [],
    validateResult: false,
    data: $student->qr_token,
    size: 320,
    margin: 8
);

$result = $builder->build();
$qrTemp = imagecreatefromstring($result->getString());

imagecopyresampled(
    $img,
    $qrTemp,
    120,
    1060,
    0,
    0,
    205,
    205,
    imagesx($qrTemp),
    imagesy($qrTemp)
);

imagedestroy($qrTemp);

imagettftext($img, 22, 0, 350, 1125, $gray, $fontBold, 'SCAN QR');
imagettftext($img, 30, 0, 350, 1175, $slate, $fontBold, 'Absensi Santri');

$token = substr($student->qr_token, 0, 28) . '...';
imagettftext($img, 18, 0, 350, 1225, $gray, $fontRegular, $token);

            // Footer
            imagettftext($img, 22, 0, 70, 1310, $textSoft, $fontBold, 'Valid Permanent');
            imagettftext($img, 22, 0, 760, 1310, $textSoft, $fontBold, now()->format('Y'));

            ob_start();
            imagepng($img);
            $png = ob_get_clean();

            imagedestroy($img);

            return response($png, 200)
                ->header('Content-Type', 'image/png')
                ->header('Content-Disposition', 'attachment; filename="ID-Card-'.$student->nis.'.png"');
        }

        private function drawRoundedRect($img, int $x1, int $y1, int $x2, int $y2, int $radius, int $color): void
        {
            imagefilledrectangle($img, $x1 + $radius, $y1, $x2 - $radius, $y2, $color);
            imagefilledrectangle($img, $x1, $y1 + $radius, $x2, $y2 - $radius, $color);

            imagefilledellipse($img, $x1 + $radius, $y1 + $radius, $radius * 2, $radius * 2, $color);
            imagefilledellipse($img, $x2 - $radius, $y1 + $radius, $radius * 2, $radius * 2, $color);
            imagefilledellipse($img, $x1 + $radius, $y2 - $radius, $radius * 2, $radius * 2, $color);
            imagefilledellipse($img, $x2 - $radius, $y2 - $radius, $radius * 2, $radius * 2, $color);
        }

        private function drawImageContain($canvas, string $path, int $x, int $y, int $w, int $h): void
        {
            if (!file_exists($path)) return;

            $src = $this->createImageFromPath($path);
            if (!$src) return;

            $sw = imagesx($src);
            $sh = imagesy($src);

            $scale = min($w / $sw, $h / $sh);
            $nw = (int) ($sw * $scale);
            $nh = (int) ($sh * $scale);

            $dx = $x + (int) (($w - $nw) / 2);
            $dy = $y + (int) (($h - $nh) / 2);

            imagecopyresampled($canvas, $src, $dx, $dy, 0, 0, $nw, $nh, $sw, $sh);
            imagedestroy($src);
        }

        private function drawImageCover($canvas, string $path, int $x, int $y, int $w, int $h): void
        {
            if (!file_exists($path)) return;

            $src = $this->createImageFromPath($path);
            if (!$src) return;

            $sw = imagesx($src);
            $sh = imagesy($src);

            $scale = max($w / $sw, $h / $sh);
            $nw = (int) ($sw * $scale);
            $nh = (int) ($sh * $scale);

            $temp = imagecreatetruecolor($w, $h);
            imagealphablending($temp, true);
            imagesavealpha($temp, true);

            $dx = (int) (($w - $nw) / 2);
            $dy = (int) (($h - $nh) / 2);

            imagecopyresampled($temp, $src, $dx, $dy, 0, 0, $nw, $nh, $sw, $sh);
            imagecopy($canvas, $temp, $x, $y, 0, 0, $w, $h);

            imagedestroy($src);
            imagedestroy($temp);
        }

        private function createImageFromPath(string $path)
        {
            if (!file_exists($path)) {
                return null;
            }

            $info = @getimagesize($path);

            if (!$info || empty($info['mime'])) {
                return null;
            }

            return match ($info['mime']) {
                'image/jpeg' => @imagecreatefromjpeg($path),
                'image/png' => @imagecreatefrompng($path),
                'image/webp' => function_exists('imagecreatefromwebp')
                    ? @imagecreatefromwebp($path)
                    : null,
                'image/gif' => @imagecreatefromgif($path),
                default => null,
            };
        }

        private function centerText($img, string $text, int $size, int $y, int $color, string $font, int $width): void
        {
            $box = imagettfbbox($size, 0, $font, $text);
            $textWidth = abs($box[2] - $box[0]);
            $x = (int) (($width - $textWidth) / 2);

            imagettftext($img, $size, 0, $x, $y, $color, $font, $text);
        }

        private function centerTextAutoSize(
            $img,
            string $text,
            int $maxSize,
            int $minSize,
            int $y,
            int $color,
            string $font,
            int $width,
            int $padding = 60
        ): void {
            $size = $maxSize;
            $maxWidth = $width - ($padding * 2);

            while ($size > $minSize) {
                $box = imagettfbbox($size, 0, $font, $text);
                $textWidth = abs($box[2] - $box[0]);

                if ($textWidth <= $maxWidth) {
                    break;
                }

                $size -= 2;
            }

            $box = imagettfbbox($size, 0, $font, $text);
            $textWidth = abs($box[2] - $box[0]);
            $x = (int) (($width - $textWidth) / 2);

            imagettftext($img, $size, 0, $x, $y, $color, $font, $text);
        }

        private function centerTextInBox($img, string $text, int $size, int $x1, int $y, int $x2, int $color, string $font): void
        {
            $box = imagettfbbox($size, 0, $font, $text);
            $textWidth = abs($box[2] - $box[0]);
            $x = (int) ($x1 + (($x2 - $x1) - $textWidth) / 2);

            imagettftext($img, $size, 0, $x, $y, $color, $font, $text);
        }
}
