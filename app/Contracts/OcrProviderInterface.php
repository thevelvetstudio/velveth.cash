<?php

namespace App\Contracts;

interface OcrProviderInterface
{
    public function extractText(string $path, string $mimeType, string $originalName): string;
}
