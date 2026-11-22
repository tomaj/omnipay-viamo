<?php

declare(strict_types=1);

namespace Omnipay\Viamo\Core;

use Omnipay\Viamo\Exception\InvalidKeyException;

/**
 * Viamo signature: the first 16 bytes of the SHA-256 hash of the input, encrypted with AES-128-ECB
 * (no padding) under the merchant key, as uppercase hexadecimal.
 */
final class ViamoSign
{
    /**
     * @param string $secret the 128-bit merchant key as 32 hexadecimal characters
     * @throws InvalidKeyException when the key is not 32 hexadecimal characters
     */
    public function sign(string $input, string $secret): string
    {
        if (preg_match('/\A[0-9a-fA-F]{32}\z/', $secret) !== 1) {
            throw new InvalidKeyException('The Viamo key must be 32 hexadecimal characters (a 128-bit key).');
        }

        $hash = substr(hash('sha256', $input, true), 0, 16);
        $sign = openssl_encrypt($hash, 'aes-128-ecb', (string) hex2bin($secret), OPENSSL_RAW_DATA | OPENSSL_ZERO_PADDING);

        return strtoupper(bin2hex((string) $sign));
    }

    /**
     * Compares a received signature with the expected one in constant time. Hexadecimal case is ignored.
     */
    public function verify(string $input, string $secret, mixed $signature): bool
    {
        if (!is_string($signature)) {
            return false;
        }

        return hash_equals($this->sign($input, $secret), strtoupper($signature));
    }
}
