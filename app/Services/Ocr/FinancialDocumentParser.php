<?php

namespace App\Services\Ocr;

use Carbon\Carbon;

class FinancialDocumentParser
{
    public function parse(string $text): array
    {
        $clean = preg_replace('/[ \t]+/', ' ', trim($text));
        return [
            'document_type' => $this->find($clean, '/\b(FACTURA(?: ELECTR[ÓO]NICA)?|RECIBO|COMPROBANTE)\b/i'),
            'invoice_number' => $this->find($clean, '/(?:FACTURA|N[ÚU]MERO|N[°º])\s*[:#-]?\s*([A-Z0-9-]{3,})/i', 1),
            'supplier_name' => $this->lineAfter($clean, ['proveedor', 'raz[oó]n social', 'comercial']) ?? $this->firstBusinessLine($clean),
            'supplier_tax_id' => $this->normalizeNit($this->find($clean, '/\b(\d{3}[.\s]?\d{3}[.\s]?\d{3}(?:-\d)?|\d{9}-\d|\d{9})\b/', 1)),
            'currency' => preg_match('/\b(COP|USD|EUR)\b|\$/i', $clean, $currency) ? (strtoupper($currency[1] ?? '') ?: 'COP') : 'COP',
            'date' => $this->findDate($clean),
            'subtotal' => $this->amountAfter($clean, ['subtotal']),
            'discount_amount' => $this->amountAfter($clean, ['descuento']),
            'tax_amount' => $this->amountAfter($clean, ['iva', 'impuesto']),
            'withholding_amount' => $this->amountAfter($clean, ['retenci[oó]n']),
            'total_amount' => $this->amountAfter($clean, ['total a pagar', 'total']),
            'raw_text' => $text,
        ];
    }

    private function find(string $text, string $pattern, int $group = 0): ?string { return preg_match($pattern, $text, $m) ? trim($m[$group] ?? '') : null; }
    private function lineAfter(string $text, array $labels): ?string { return $this->find($text, '/(?:'.implode('|', $labels).')\s*[:\-]?\s*([^\n]{3,})/i', 1); }
    private function firstBusinessLine(string $text): ?string { foreach (preg_split('/\R/', $text) as $line) if (strlen(trim($line)) >= 4 && !preg_match('/\d{4,}|factura|fecha|total|subtotal/i', $line)) return trim($line); return null; }
    private function normalizeNit(?string $nit): ?string { if (!$nit) return null; $digits = preg_replace('/\D/', '', $nit); return strlen($digits) >= 10 ? substr($digits, 0, 9).'-'.substr($digits, 9) : (strlen($digits) === 9 ? $digits : null); }
    private function findDate(string $text): ?string { if (preg_match('/\b(\d{4})-(\d{1,2})-(\d{1,2})\b/', $text, $iso)) return checkdate((int) $iso[2], (int) $iso[3], (int) $iso[1]) ? sprintf('%04d-%02d-%02d', $iso[1], $iso[2], $iso[3]) : null; if (!preg_match('/\b(\d{1,2})[\/-](\d{1,2})[\/-](\d{4})\b/', $text, $m)) return null; try { return Carbon::createFromFormat('d/m/Y', "$m[1]/$m[2]/$m[3]")->format('Y-m-d'); } catch (\Throwable) { return null; } }
    private function amountAfter(string $text, array $labels): ?float { $value = $this->find($text, '/\b(?:'.implode('|', $labels).')\b\s*[:\-]?\s*(?:COP\s*)?\$?\s*([\d.,]+)/i', 1); return $value ? $this->normalizeAmount($value) : null; }
    private function normalizeAmount(string $value): float { $value = preg_replace('/[^\d.,]/', '', $value); $lastComma = strrpos($value, ','); $lastDot = strrpos($value, '.'); if ($lastComma !== false && $lastDot !== false) { $decimal = max($lastComma, $lastDot); $fraction = substr($value, $decimal + 1); if (strlen($fraction) <= 2) return (float) str_replace(',', '.', str_replace(['.', ','], ['', '.'], substr($value, 0, $decimal).'.'.$fraction)); } if ($lastComma !== false && strlen($value) - $lastComma - 1 <= 2) return (float) str_replace(',', '.', str_replace('.', '', $value)); if ($lastDot !== false && strlen($value) - $lastDot - 1 <= 2) return (float) $value; return (float) str_replace(['.', ','], '', $value); }
}
