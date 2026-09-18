<?php

namespace Tests\Unit;

use App\Services\Ocr\FinancialDocumentParser;
use Tests\TestCase;

class FinancialDocumentParserTest extends TestCase
{
    public function test_parses_colombian_invoice_values(): void
    {
        $result = (new FinancialDocumentParser())->parse("XYZ SAS\nNIT: 901.234.567-8\nFactura: FEV-18392\nFecha: 17/09/2026\nSUBTOTAL $840.336\nIVA $159.664\nTOTAL A PAGAR $1.000.000");

        $this->assertSame('901234567-8', $result['supplier_tax_id']);
        $this->assertSame('FEV-18392', $result['invoice_number']);
        $this->assertSame('2026-09-17', $result['date']);
        $this->assertSame(840336.0, $result['subtotal']);
        $this->assertSame(159664.0, $result['tax_amount']);
        $this->assertSame(1000000.0, $result['total_amount']);
    }

    public function test_does_not_invent_missing_values(): void
    {
        $result = (new FinancialDocumentParser())->parse('Documento sin valores identificables');

        $this->assertNull($result['invoice_number']);
        $this->assertNull($result['total_amount']);
    }
}
