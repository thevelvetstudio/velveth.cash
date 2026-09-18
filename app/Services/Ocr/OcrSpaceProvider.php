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

        foreach ([2, 1] as $engine) {
            $response = Http::timeout((int) config('services.ocr_space.timeout', 45))
                ->withHeaders(['apikey' => $key])
                ->attach('file', fopen($path, 'r'), $originalName)
                ->post(config('services.ocr_space.endpoint'), [
                    'language' => 'spa', 'isOverlayRequired' => 'false',
                    'OCREngine' => (string) $engine, 'detectOrientation' => 'true',
                ]);
            if ($response->failed()) throw new RuntimeException('Proveedor OCR no disponible.');
            $payload = $response->json();
            if (!empty($payload['IsErroredOnProcessing'])) throw new RuntimeException('Documento ilegible.');
            $text = collect($payload['ParsedResults'] ?? [])->pluck('ParsedText')->filter()->implode("\n");
            if (trim($text) !== '') return $text;
        }
        throw new RuntimeException('Documento sin texto legible.');
    }
}
