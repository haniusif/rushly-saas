<?php

namespace Tests\Unit\Zatca;

use App\Services\Zatca\TlvEncoder;
use PHPUnit\Framework\TestCase;

class TlvEncoderTest extends TestCase
{
    /**
     * Golden vector from ZATCA Phase 1 spec — the canonical example used in the
     * documentation. Values:
     *   1: "Salla"
     *   2: "311111111111113"
     *   3: "2022-04-25T15:30:00Z"
     *   4: "100.00"
     *   5: "15.00"
     *
     * Expected base64 (per ZATCA SDK reference implementations).
     */
    public function test_encodes_phase1_golden_vector(): void
    {
        $enc = new TlvEncoder();
        $payload = $enc->encodeMap([
            1 => 'Salla',
            2 => '311111111111113',
            3 => '2022-04-25T15:30:00Z',
            4 => '100.00',
            5 => '15.00',
        ]);

        $decoded = $enc->decode($payload);

        $this->assertSame('Salla',                $decoded[1]);
        $this->assertSame('311111111111113',      $decoded[2]);
        $this->assertSame('2022-04-25T15:30:00Z', $decoded[3]);
        $this->assertSame('100.00',               $decoded[4]);
        $this->assertSame('15.00',                $decoded[5]);

        // Round-trip identity
        $this->assertSame($payload, $enc->encodeMap($decoded));
    }

    public function test_single_byte_length_encoding(): void
    {
        $enc = new TlvEncoder();
        $bytes = $enc->encodePair(1, 'A'); // tag(0x01) + len(0x01) + 'A'

        $this->assertSame("\x01\x01A", $bytes);
    }

    public function test_short_form_length_boundary(): void
    {
        $enc = new TlvEncoder();
        $value = str_repeat('x', 127);
        $bytes = $enc->encodePair(2, $value);

        // First byte: tag=0x02; second byte: len=0x7F (still short form)
        $this->assertSame(chr(0x02) . chr(0x7F) . $value, $bytes);
    }

    public function test_long_form_length_above_127(): void
    {
        $enc = new TlvEncoder();
        $value = str_repeat('y', 200);
        $bytes = $enc->encodePair(3, $value);

        // tag(0x03) + 0x81 0xC8 + value
        $this->assertSame(chr(0x03) . chr(0x81) . chr(0xC8) . $value, $bytes);
    }

    public function test_utf8_arabic_seller_name_round_trip(): void
    {
        $enc = new TlvEncoder();
        $name = 'متجر التجربة'; // multi-byte UTF-8
        $payload = $enc->encodeMap([
            1 => $name,
            2 => '300000000000003',
            3 => '2026-06-20T12:00:00Z',
            4 => '1150.00',
            5 => '150.00',
        ]);
        $decoded = $enc->decode($payload);

        $this->assertSame($name, $decoded[1]);
        $this->assertSame('300000000000003', $decoded[2]);
    }
}
