<?php

namespace App\Services\Ocr;

use Carbon\Carbon;
use Illuminate\Support\Str;

class FinancialDocumentParser
{
    public function parse(string $text): array
    {
        $normalized = implode("\n", array_map(
            static fn (string $line): string => preg_replace('/[ \t]+/', ' ', trim(Str::ascii($line))),
            preg_split('/\R/', trim($text)) ?: [],
        ));

        $subtotal = $this->amountAfter($normalized, ['SUBTOTAL', 'BASE GRAVABLE']);
        $discount = $this->amountAfter($normalized, ['DESCUENTO', 'DESCUENTOS']);
        $tax = $this->amountAfter($normalized, ['IVA', 'IMPUESTO', 'IMPUESTOS']);
        $total = $this->amountAfter($normalized, ['TOTAL A PAGAR', 'VALOR A PAGAR', 'TOTAL']);
        $lineItems = $this->extractLineItems($normalized);
        $validation = $this->validateTotals($subtotal, $discount, $tax, $total, $lineItems);

        return [
            'document_type' => $this->find($normalized, '/\b(FACTURA(?: ELECTRONICA)?|RECIBO|COMPROBANTE|SOAT)\b/i'),
            'invoice_number' => $this->find($normalized, '/(?:FACTURA(?:\s+DE\s+VENTA)?|NUMERO|NRO|NO\.?|FEV)\s*[:#-]?\s*(?:NO\.?\s*)?([A-Z0-9][A-Z0-9-]{2,})/i', 1),
            'supplier_name' => $this->lineAfter($normalized, ['PROVEEDOR', 'RAZON SOCIAL', 'NOMBRE', 'COMERCIAL']) ?? $this->firstBusinessLine($text),
            'supplier_tax_id' => $this->normalizeNit($this->find($normalized, '/\b(\d{3}[.\s]?\d{3}[.\s]?\d{3}(?:-\d)?|\d{9}-\d|\d{9})\b/', 1)),
            'currency' => preg_match('/\b(COP|USD|EUR)\b|\$/i', $normalized, $currency) ? (strtoupper($currency[1] ?? '') ?: 'COP') : 'COP',
            'date' => $this->findDate($normalized),
            'subtotal' => $subtotal,
            'discount_amount' => $discount,
            'tax_amount' => $tax,
            'withholding_amount' => $this->amountAfter($normalized, ['RETENCION', 'RETEFUENTE']),
            'total_amount' => $total,
            'line_items' => $lineItems,
            'totals_valid' => $validation['valid'],
            'totals_difference' => $validation['difference'],
            'confidence' => $this->confidence($normalized, $total, $lineItems),
            'raw_text' => $text,
        ];
    }

    private function find(string $text, string $pattern, int $group = 0): ?string { return preg_match($pattern, $text, $matches) ? trim($matches[$group] ?? '') : null; }
    private function lineAfter(string $text, array $labels): ?string { return $this->find($text, '/(?:'.implode('|', $labels).')\s*[:\-]?\s*([^\n]{3,})/i', 1); }
    private function firstBusinessLine(string $text): ?string { foreach (preg_split('/\R/', $text) as $line) { $line = trim($line); $normalized = Str::ascii($line); if (strlen($line) >= 4 && !preg_match('/\d{4,}|FACTURA|FECHA|TOTAL|SUBTOTAL|NIT|DIAN/i', $normalized)) return $line; } return null; }
    private function normalizeNit(?string $nit): ?string { if (!$nit) return null; $digits = preg_replace('/\D/', '', $nit); return strlen($digits) >= 10 ? substr($digits, 0, 9).'-'.substr($digits, 9, 1) : (strlen($digits) === 9 ? $digits : null); }
    private function findDate(string $text): ?string { if (preg_match('/\b(\d{4})-(\d{1,2})-(\d{1,2})\b/', $text, $iso)) return checkdate((int) $iso[2], (int) $iso[3], (int) $iso[1]) ? sprintf('%04d-%02d-%02d', $iso[1], $iso[2], $iso[3]) : null; if (!preg_match('/\b(\d{1,2})[\/-](\d{1,2})[\/-](\d{4})\b/', $text, $date)) return null; try { return Carbon::createFromFormat('d/m/Y', "$date[1]/$date[2]/$date[3]")->format('Y-m-d'); } catch (\Throwable) { return null; } }
    private function amountAfter(string $text, array $labels): ?float { $value = $this->find($text, '/\b(?:'.implode('|', $labels).')\b\s*[:\-]?\s*(?:COP\s*)?\$?\s*([\d.,]+)/i', 1); return $value ? $this->normalizeAmount($value) : null; }
    private function extractLineItems(string $text): array
    {
        $items = [];
        foreach (preg_split('/\R/', $text) as $line) {
            $line = trim(preg_replace('/[ \t]+/', ' ', $line));
            if (preg_match('/^(?:\d+\s+DESCRIPCION|CANT\.?\s+|SUBTOTAL|TOTAL A PAGAR|VALOR UNITARIO)/i', $line)) continue;
            if (preg_match('/^(\d+)\s+X\s+(.+?)\s+(?:\$\s*)?([\d.,]+)$/i', $line, $receiptMatch)) {
                $amount = $this->normalizeAmount($receiptMatch[3]);
                $items[] = ['quantity' => (int) $receiptMatch[1], 'description' => trim($receiptMatch[2]), 'unit_price' => $amount, 'tax_rate' => null, 'total' => $amount * (int) $receiptMatch[1]];
                continue;
            }
            if (!preg_match('/^(\d+)\s+(.+?)\s+(?:\$\s*)?([\d.,]+)\s+(?:(\d{1,2})%\s+)?(?:\$\s*)?([\d.,]+)$/i', $line, $match)) continue;
            $items[] = ['quantity' => (int) $match[1], 'description' => trim($match[2]), 'unit_price' => $this->normalizeAmount($match[3]), 'tax_rate' => isset($match[4]) && $match[4] !== '' ? (float) $match[4] : null, 'total' => $this->normalizeAmount($match[5])];
        }
        return $items;
    }
    private function validateTotals(?float $subtotal, ?float $discount, ?float $tax, ?float $total, array $items = []): array
    {
        if ($total === null) return ['valid' => null, 'difference' => null];
        $expected = $subtotal !== null
            ? $subtotal - ($discount ?? 0) + ($tax ?? 0)
            : (count($items) > 0 ? array_sum(array_column($items, 'total')) : null);
        if ($expected === null) return ['valid' => null, 'difference' => null];
        $difference = round($total - $expected, 2);
        return ['valid' => abs($difference) <= 1, 'difference' => $difference];
    }
    private function confidence(string $text, ?float $total, array $items): string
    {
        $signals = 0;
        foreach ([preg_match('/\bNIT\b/i', $text), preg_match('/\bFACTURA\b/i', $text), preg_match('/\bFECHA\b/i', $text), $total !== null, count($items) > 0] as $signal) $signals += $signal ? 1 : 0;
        return $signals >= 4 ? 'Alta' : ($signals >= 2 ? 'Media' : 'Baja');
    }
    private function normalizeAmount(string $value): float { $value = preg_replace('/[^\d.,]/', '', $value); $lastComma = strrpos($value, ','); $lastDot = strrpos($value, '.'); if ($lastComma !== false && $lastDot !== false) { $decimalIndex = max($lastComma, $lastDot); $fraction = substr($value, $decimalIndex + 1); if (strlen($fraction) <= 2) return (float) str_replace(',', '.', str_replace(['.', ','], ['', '.'], substr($value, 0, $decimalIndex).'.'.$fraction)); } if ($lastComma !== false && strlen($value) - $lastComma - 1 <= 2) return (float) str_replace(',', '.', str_replace('.', '', $value)); if ($lastDot !== false && strlen($value) - $lastDot - 1 <= 2) return (float) $value; return (float) str_replace(['.', ','], '', $value); }
}
