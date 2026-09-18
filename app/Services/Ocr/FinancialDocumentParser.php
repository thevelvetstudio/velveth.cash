<?php

namespace App\Services\Ocr;

use Carbon\Carbon;
use Illuminate\Support\Str;

class FinancialDocumentParser
{
    public function parse(string $text): array
    {
        $normalized = Str::ascii($text);
        $normalized = preg_replace('/[ \t]+/', ' ', trim($normalized));

        return [
            'document_type' => $this->find($normalized, '/\b(FACTURA(?: ELECTRONICA)?|RECIBO|COMPROBANTE|SOAT)\b/i'),
            'invoice_number' => $this->find($normalized, '/(?:FACTURA|NUMERO|NRO|NO\.?|FEV)\s*[:#-]?\s*([A-Z0-9][A-Z0-9-]{2,})/i', 1),
            'supplier_name' => $this->lineAfter($normalized, ['PROVEEDOR', 'RAZON SOCIAL', 'NOMBRE', 'COMERCIAL']) ?? $this->firstBusinessLine($text),
            'supplier_tax_id' => $this->normalizeNit($this->find($normalized, '/\b(\d{3}[.\s]?\d{3}[.\s]?\d{3}(?:-\d)?|\d{9}-\d|\d{9})\b/', 1)),
            'currency' => preg_match('/\b(COP|USD|EUR)\b|\$/i', $normalized, $currency) ? (strtoupper($currency[1] ?? '') ?: 'COP') : 'COP',
            'date' => $this->findDate($normalized),
            'subtotal' => $this->amountAfter($normalized, ['SUBTOTAL', 'BASE GRAVABLE']),
            'discount_amount' => $this->amountAfter($normalized, ['DESCUENTO', 'DESCUENTOS']),
            'tax_amount' => $this->amountAfter($normalized, ['IVA', 'IMPUESTO', 'IMPUESTOS']),
            'withholding_amount' => $this->amountAfter($normalized, ['RETENCION', 'RETEFUENTE']),
            'total_amount' => $this->amountAfter($normalized, ['TOTAL A PAGAR', 'VALOR A PAGAR', 'TOTAL']),
            'raw_text' => $text,
        ];
    }

    private function find(string $text, string $pattern, int $group = 0): ?string { return preg_match($pattern, $text, $matches) ? trim($matches[$group] ?? '') : null; }
    private function lineAfter(string $text, array $labels): ?string { return $this->find($text, '/(?:'.implode('|', $labels).')\s*[:\-]?\s*([^\n]{3,})/i', 1); }
    private function firstBusinessLine(string $text): ?string { foreach (preg_split('/\R/', $text) as $line) { $line = trim($line); $normalized = Str::ascii($line); if (strlen($line) >= 4 && !preg_match('/\d{4,}|FACTURA|FECHA|TOTAL|SUBTOTAL|NIT|DIAN/i', $normalized)) return $line; } return null; }
    private function normalizeNit(?string $nit): ?string { if (!$nit) return null; $digits = preg_replace('/\D/', '', $nit); return strlen($digits) >= 10 ? substr($digits, 0, 9).'-'.substr($digits, 9, 1) : (strlen($digits) === 9 ? $digits : null); }
    private function findDate(string $text): ?string { if (preg_match('/\b(\d{4})-(\d{1,2})-(\d{1,2})\b/', $text, $iso)) return checkdate((int) $iso[2], (int) $iso[3], (int) $iso[1]) ? sprintf('%04d-%02d-%02d', $iso[1], $iso[2], $iso[3]) : null; if (!preg_match('/\b(\d{1,2})[\/-](\d{1,2})[\/-](\d{4})\b/', $text, $date)) return null; try { return Carbon::createFromFormat('d/m/Y', "$date[1]/$date[2]/$date[3]")->format('Y-m-d'); } catch (\Throwable) { return null; } }
    private function amountAfter(string $text, array $labels): ?float { $value = $this->find($text, '/\b(?:'.implode('|', $labels).')\b\s*[:\-]?\s*(?:COP\s*)?\$?\s*([\d.,]+)/i', 1); return $value ? $this->normalizeAmount($value) : null; }
    private function normalizeAmount(string $value): float { $value = preg_replace('/[^\d.,]/', '', $value); $lastComma = strrpos($value, ','); $lastDot = strrpos($value, '.'); if ($lastComma !== false && $lastDot !== false) { $decimalIndex = max($lastComma, $lastDot); $fraction = substr($value, $decimalIndex + 1); if (strlen($fraction) <= 2) return (float) str_replace(',', '.', str_replace(['.', ','], ['', '.'], substr($value, 0, $decimalIndex).'.'.$fraction)); } if ($lastComma !== false && strlen($value) - $lastComma - 1 <= 2) return (float) str_replace(',', '.', str_replace('.', '', $value)); if ($lastDot !== false && strlen($value) - $lastDot - 1 <= 2) return (float) $value; return (float) str_replace(['.', ','], '', $value); }
}
