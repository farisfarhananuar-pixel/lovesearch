<?php

namespace App\Support;

use Illuminate\Http\UploadedFile;

/**
 * Tukar gambar yang diupload terus jadi base64 data URI utk disimpan dalam DB.
 *
 * Sebab: hosting (Railway) guna storage local yang "ephemeral" - fail yang
 * diupload (QR code, gambar profil, resit bayaran) boleh hilang lepas
 * redeploy/restart container. Dengan simpan terus dalam DB (MySQL), gambar
 * akan kekal selama-lamanya tanpa bergantung pada disk storage.
 *
 * Gambar akan di-resize & dimampatkan dahulu (kalau extension GD ada) supaya
 * saiz dalam DB kekal kecil dan laman pantas dimuat.
 */
class ImageCompressor
{
    /**
     * @param  string  $mode  'photo' (muka/profil - JPEG, lebih dimampat) atau
     *                         'document' (QR/resit - PNG, kurang dimampat supaya tetap jelas)
     */
    public static function toDataUri(UploadedFile $file, string $mode = 'photo', int $maxDimension = 480): string
    {
        $raw = file_get_contents($file->getRealPath());

        if (! extension_loaded('gd') || ! function_exists('imagecreatefromstring')) {
            // Fallback selamat kalau GD tak ada - simpan terus tanpa resize.
            return 'data:'.$file->getMimeType().';base64,'.base64_encode($raw);
        }

        $source = @imagecreatefromstring($raw);

        if ($source === false) {
            return 'data:'.$file->getMimeType().';base64,'.base64_encode($raw);
        }

        $width = imagesx($source);
        $height = imagesy($source);

        $maxDim = $mode === 'document' ? max($maxDimension, 700) : $maxDimension;

        if ($width > $maxDim || $height > $maxDim) {
            $ratio = min($maxDim / $width, $maxDim / $height);
            $newWidth = (int) round($width * $ratio);
            $newHeight = (int) round($height * $ratio);

            $resized = imagecreatetruecolor($newWidth, $newHeight);

            // Kekalkan latar belakang putih/lutsinar utk PNG (penting utk QR code).
            imagefill($resized, 0, 0, imagecolorallocate($resized, 255, 255, 255));
            imagealphablending($resized, true);
            imagesavealpha($resized, true);

            imagecopyresampled($resized, $source, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
            imagedestroy($source);
            $source = $resized;
        }

        ob_start();

        if ($mode === 'document') {
            // PNG - lossless, penting utk QR code & teks dalam resit kekal jelas.
            imagepng($source, null, 6);
            $mime = 'image/png';
        } else {
            // JPEG - cukup utk gambar profil/muka, saiz fail jauh lebih kecil.
            imagejpeg($source, null, 78);
            $mime = 'image/jpeg';
        }

        $bytes = ob_get_clean();
        imagedestroy($source);

        return 'data:'.$mime.';base64,'.base64_encode($bytes);
    }
}
