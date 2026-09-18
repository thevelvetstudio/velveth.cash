<?php

namespace App\Services\Ocr;

use App\Contracts\OcrProviderInterface;
use RuntimeException;

class OcrService
{
    public function __construct(private readonly OcrProviderInterface $provider) {}

    public function extract(string $path, string $mimeType, string $originalName): string
    {
        if (!config('services.ocr.enabled', true)) {
            throw new RuntimeException('OCR no configurado.');
        }

        return $this->provider->extractText($path, $mimeType, $originalName);
    }
}
