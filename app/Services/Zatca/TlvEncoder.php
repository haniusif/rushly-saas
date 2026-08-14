<?php

namespace App\Services\Zatca;

use InvalidArgumentException;

/**
 * Tag-Length-Value encoder for ZATCA Phase 1 QR codes.
 *
 * Phase 1 mandatory tags:
 *   1: Seller name (UTF-8)
 *   2: VAT registration number (15 digits)
 *   3: Invoice timestamp (ISO 8601 UTC, e.g. 2026-06-20T15:30:00Z)
 *   4: Invoice total with VAT (string decimal)
 *   5: VAT total (string decimal)
 */
final class TlvEncoder
{
    /**
     * Encode an associative array of tag => value into a single base64-encoded TLV blob.
     *
     * @param array<int,string> $tlvs  e.g. [1 => 'Seller', 2 => '300000000000003', ...]
     */
    public function encodeMap(array $tlvs): string
    {
        $binary = '';
        foreach ($tlvs as $tag => $value) {
            $binary .= $this->encodePair((int) $tag, (string) $value);
        }
        return base64_encode($binary);
    }

    /**
     * Encode a single TLV triplet to raw bytes.
     */
    public function encodePair(int $tag, string $value): string
    {
        if ($tag < 0 || $tag > 255) {
            throw new InvalidArgumentException("TLV tag out of range: {$tag}");
        }

        $length = strlen($value);

        return chr($tag) . $this->encodeLength($length) . $value;
    }

    /**
     * ZATCA uses 1-byte length for values up to 255 bytes. Longer values
     * (Phase 2 signatures) use BER long-form. We support both for forward compat.
     */
    private function encodeLength(int $length): string
    {
        if ($length < 0) {
            throw new InvalidArgumentException('TLV length cannot be negative');
        }
        if ($length <= 0x7F) {
            return chr($length);
        }
        if ($length <= 0xFF) {
            return chr(0x81) . chr($length);
        }
        if ($length <= 0xFFFF) {
            return chr(0x82) . chr(($length >> 8) & 0xFF) . chr($length & 0xFF);
        }
        throw new InvalidArgumentException("TLV value too large: {$length} bytes");
    }

    /**
     * Decode a base64 TLV blob back into [tag => value]. Used for testing.
     *
     * @return array<int,string>
     */
    public function decode(string $base64): array
    {
        $binary = base64_decode($base64, true);
        if ($binary === false) {
            throw new InvalidArgumentException('Invalid base64 TLV payload');
        }

        $out = [];
        $i = 0;
        $len = strlen($binary);

        while ($i < $len) {
            $tag = ord($binary[$i++]);
            $first = ord($binary[$i++]);

            if ($first <= 0x7F) {
                $valueLen = $first;
            } elseif ($first === 0x81) {
                $valueLen = ord($binary[$i++]);
            } elseif ($first === 0x82) {
                $valueLen = (ord($binary[$i++]) << 8) | ord($binary[$i++]);
            } else {
                throw new InvalidArgumentException('Unsupported TLV length prefix');
            }

            $out[$tag] = substr($binary, $i, $valueLen);
            $i += $valueLen;
        }

        return $out;
    }
}
