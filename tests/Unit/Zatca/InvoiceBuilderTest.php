<?php

namespace Tests\Unit\Zatca;

use App\Services\Zatca\InvoiceBuilder;
use App\Services\Zatca\QrGenerator;
use App\Services\Zatca\TlvEncoder;
use PHPUnit\Framework\TestCase;

class InvoiceBuilderTest extends TestCase
{
    public function test_splits_15pct_vat_from_inclusive_total(): void
    {
        $builder = new InvoiceBuilder(new TlvEncoder(), $this->stubQr());

        // 115.00 inclusive at 15% → 100.00 net + 15.00 VAT
        [$net, $vat] = $builder->splitVatInclusive(115.00, 15.0);

        $this->assertSame(100.00, $net);
        $this->assertSame(15.00, $vat);
    }

    public function test_splits_zero_vat_keeps_subtotal_equal_to_total(): void
    {
        $builder = new InvoiceBuilder(new TlvEncoder(), $this->stubQr());

        [$net, $vat] = $builder->splitVatInclusive(250.00, 0.0);

        $this->assertSame(250.00, $net);
        $this->assertSame(0.00, $vat);
    }

    public function test_rounds_to_two_decimals(): void
    {
        $builder = new InvoiceBuilder(new TlvEncoder(), $this->stubQr());

        // 99.99 / 1.15 = 86.948... → 86.95 net, 13.04 vat
        [$net, $vat] = $builder->splitVatInclusive(99.99, 15.0);

        $this->assertSame(86.95, $net);
        $this->assertSame(13.04, $vat);
        $this->assertEqualsWithDelta(99.99, $net + $vat, 0.01);
    }

    public function test_handles_non_standard_vat_rate(): void
    {
        $builder = new InvoiceBuilder(new TlvEncoder(), $this->stubQr());

        // 105 inclusive at 5% → 100.00 net + 5.00 VAT
        [$net, $vat] = $builder->splitVatInclusive(105.00, 5.0);

        $this->assertSame(100.00, $net);
        $this->assertSame(5.00, $vat);
    }

    private function stubQr(): QrGenerator
    {
        return new QrGenerator();
    }
}
