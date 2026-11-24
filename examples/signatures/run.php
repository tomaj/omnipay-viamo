<?php

declare(strict_types=1);

// The Viamo signature by hand: what ViamoSign computes, how verify() compares, and the
// exceptions a misconfigured gateway throws. e2e.sh recomputes every signature with openssl.

use Omnipay\Common\Exception\InvalidRequestException;
use Omnipay\Omnipay;
use Omnipay\Viamo\Core\ViamoSign;
use Omnipay\Viamo\Exception\InvalidKeyException;
use Omnipay\Viamo\Exception\ViamoException;

require __DIR__ . '/vendor/autoload.php';

$key1 = '000102030405060708090a0b0c0d0e0f';
$key2 = 'F0E1D2C3B4A5968778695A4B3C2D1E0F';
$sign = new ViamoSign();

// One line per signature: key, signature, signed text (e2e.sh checks each one with openssl).
$inputs = [
    [$key1, 'QP:1.0*BID:42*AM:24.90*CC:EUR*VS:2026000123*MSG:Order 2026000123'], // payment request
    [$key1, 'BID:42*VS:2026000123*RES:OK'],                                     // return URL
    [$key2, 'R-772026000123E2E-2026000123OK24.90555001'],                       // webhook: rid vs e2e result amount id
    [$key1, 'QP:1.0*BID:42*AM:5.00*MSG:Ďakujeme za nákup'],                     // UTF-8 bytes are signed as they are
];
foreach ($inputs as [$key, $text]) {
    echo 'sign ', $key, ' ', $sign->sign($text, $key), ' ', $text, PHP_EOL;
}

// verify() compares in constant time (hash_equals) and ignores the hexadecimal case.
$signature = $sign->sign('BID:42*VS:2026000123*RES:OK', $key1);
$checks = [
    'same signature' => $signature,
    'lowercase' => strtolower($signature),
    'one character changed' => substr_replace($signature, $signature[0] === 'A' ? 'B' : 'A', 0, 1),
    'truncated' => substr($signature, 0, 16),
    'empty' => '',
    'not a string' => 12345,
];
foreach ($checks as $label => $candidate) {
    printf("verify %-22s %s\n", $label, var_export($sign->verify('BID:42*VS:2026000123*RES:OK', $key1, $candidate), true));
}

// A key must be 128 bits as 32 hexadecimal characters; anything else throws instead of
// signing with a wrong key.
foreach (['too short' => 'abc', 'not hex' => str_repeat('g', 32), '256-bit' => str_repeat('0', 64)] as $label => $badKey) {
    try {
        $sign->sign('x', $badKey);
    } catch (InvalidKeyException $e) {
        echo "key {$label}: ", $e::class, ': ', $e->getMessage(), PHP_EOL;
    }
}

// The same through the gateway: every package exception implements ViamoException;
// a missing required parameter is Omnipay's InvalidRequestException.
$gateway = Omnipay::create('Viamo')->initialize(['bid' => '42', 'key1' => 'wrong', 'key2' => $key2]);
try {
    $gateway->purchase(['amount' => '1.00'])->send();
} catch (ViamoException $e) {
    echo 'purchase() with a wrong key1: ', $e::class, PHP_EOL;
}
try {
    $gateway->webhook()->send(); // no 'json'
} catch (InvalidRequestException $e) {
    echo 'webhook() without json: ', $e::class, ': ', $e->getMessage(), PHP_EOL;
}
