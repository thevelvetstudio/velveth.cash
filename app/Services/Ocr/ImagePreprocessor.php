<?php

namespace App\Services\Ocr;

use RuntimeException;

class ImagePreprocessor
{
    /** @return array{path:string,mime_type:string,original_name:string,temporary:bool} */
    public function prepare(string $path, string $mimeType, string $originalName): array
    {
        if (!str_starts_with($mimeType, 'image/') || !function_exists('imagecreatefromstring')) {
            return compact('path', 'mimeType', 'originalName') + ['mime_type' => $mimeType, 'temporary' => false];
        }

        $contents = file_get_contents($path);
        $source = $contents === false ? false : @imagecreatefromstring($contents);
        if (!$source) return ['path' => $path, 'mime_type' => $mimeType, 'original_name' => $originalName, 'temporary' => false];

        $oriented = $this->correctOrientation($source, $path);
        if ($oriented !== $source) imagedestroy($source);
        $source = $oriented;
        $width = imagesx($source);
        $height = imagesy($source);
        $maxWidth = 2400;
        $targetWidth = min($width, $maxWidth);
        $targetHeight = (int) round($height * ($targetWidth / max($width, 1)));
        $canvas = imagecreatetruecolor($targetWidth, $targetHeight);
        $white = imagecolorallocate($canvas, 255, 255, 255);
        imagefill($canvas, 0, 0, $white);
        imagecopyresampled($canvas, $source, 0, 0, 0, 0, $targetWidth, $targetHeight, $width, $height);
        imagedestroy($source);

        imagefilter($canvas, IMG_FILTER_GRAYSCALE);
        imagefilter($canvas, IMG_FILTER_CONTRAST, -12);
        $temporaryPath = tempnam(storage_path('app'), 'ocr-prepared-');
        if (!$temporaryPath || !imagejpeg($canvas, $temporaryPath, 88)) {
            imagedestroy($canvas);
            throw new RuntimeException('No fue posible preparar la imagen.');
        }
        imagedestroy($canvas);

        return ['path' => $temporaryPath, 'mime_type' => 'image/jpeg', 'original_name' => pathinfo($originalName, PATHINFO_FILENAME).'.jpg', 'temporary' => true];
    }

    private function correctOrientation($image, string $path)
    {
        if (!function_exists('exif_read_data')) return $image;
        $metadata = @exif_read_data($path);
        $orientation = (int) ($metadata['Orientation'] ?? 1);
        $degrees = match ($orientation) { 3 => 180, 6 => -90, 8 => 90, default => 0 };
        if ($degrees !== 0) {
            $rotated = imagerotate($image, $degrees, 0);
            if ($rotated) return $rotated;
        }
        return $image;
    }
}
