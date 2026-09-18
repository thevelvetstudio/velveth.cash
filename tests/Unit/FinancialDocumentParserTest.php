<?php

namespace Tests\Unit;

use App\Services\Ocr\FinancialDocumentParser;
use Tests\TestCase;

class FinancialDocumentParserTest extends TestCase
{
    public function test_parses_colombian_invoice_values(): void
    {
        $result = (new FinancialDocumentParser())->parse("XYZ SAS\nNIT: 901.234.567-8\nFactura: FEV-18392\nFecha: 17/09/2026\n1 Portatil Lenovo $ 840.336 19% $ 840.336\nSUBTOTAL $840.336\nIVA $159.664\nTOTAL A PAGAR $1.000.000");

        $this->assertSame('901234567-8', $result['supplier_tax_id']);
        $this->assertSame('FEV-18392', $result['invoice_number']);
        $this->assertSame('2026-09-17', $result['date']);
        $this->assertSame(840336.0, $result['subtotal']);
        $this->assertSame(159664.0, $result['tax_amount']);
        $this->assertSame(1000000.0, $result['total_amount']);
        $this->assertCount(1, $result['line_items']);
        $this->assertTrue($result['totals_valid']);
    }

    public function test_does_not_invent_missing_values(): void
    {
        $result = (new FinancialDocumentParser())->parse('Documento sin valores identificables');

        $this->assertNull($result['invoice_number']);
        $this->assertNull($result['total_amount']);
    }

    public function test_parses_thermal_receipt_line_items_and_total(): void
    {
        $result = (new FinancialDocumentParser())->parse("MINIMARKET LA ESQUINA\nNIT 901.234.567-8\nFecha: 2024-09-17\nFactura de Venta No. 001-000123\n1 X Agua Cristal 600 ml 2.500\n1 X Galletas Saltin 3.200\n1 X Juguito Hit 200 ml 2.800\nTOTAL $ 8.500");

        $this->assertSame('001-000123', $result['invoice_number']);
        $this->assertSame('2024-09-17', $result['date']);
        $this->assertCount(3, $result['line_items']);
        $this->assertSame(8500.0, $result['total_amount']);
        $this->assertTrue($result['totals_valid']);
    }
}
