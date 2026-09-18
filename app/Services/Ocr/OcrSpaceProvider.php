<?php

namespace App\Services\Ocr;

use App\Contracts\OcrProviderInterface;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class OcrSpaceProvider implements OcrProviderInterface
{
    public function extractText(string $path, string $mimeType, string $originalName): string
    {
        $key = config('services.ocr_space.api_key');
        if (!$key) throw new RuntimeException('OCR no configurado.');

        $response = Http::timeout((int) config('services.ocr_space.timeout', 45))
            ->attach('file', fopen($path, 'r'), $originalName)
            ->post(config('services.ocr_space.endpoint'), [
                'apikey' => $key,
                'language' => 'spa',
                'isOverlayRequired' => 'false',
                'OCREngine' => '2',
                'detectOrientation' => 'true',
            ]);

        if ($response->failed()) throw new RuntimeException('Proveedor OCR no disponible.');
        $payload = $response->json();
        if (!empty($payload['IsErroredOnProcessing'])) throw new RuntimeException('Documento ilegible.');

        $text = collect($payload['ParsedResults'] ?? [])->pluck('ParsedText')->filter()->implode("\n");
        if (trim($text) === '') throw new RuntimeException('Documento sin texto legible.');

        return $text;
    }
}
