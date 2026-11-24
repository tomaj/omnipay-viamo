<?php

declare(strict_types=1);

// Plays the Viamo app: sends the customer back to the shop's return URL with a responseString
// signed the way Viamo signs it (ViamoSign with key1), plus a few forged ones.

use Omnipay\Viamo\Core\ViamoSign;

require __DIR__ . '/vendor/autoload.php';

$shop = 'http://127.0.0.1:8361/viamo/return';
$key1 = getenv('VIAMO_KEY1') ?: '000102030405060708090a0b0c0d0e0f';
$sign = new ViamoSign();
$signed = static fn(string $fields): string => $fields . '*SIG:' . $sign->sign($fields, $key1);

$returns = [
    'paid' => $signed('BID:42*VS:2026000123*RES:OK'),
    'paid again (reload)' => $signed('BID:42*VS:2026000123*RES:OK'),
    'declined' => $signed('BID:42*VS:2026000124*RES:FAIL'),
    'forged: RES changed to OK' => str_replace('RES:FAIL', 'RES:OK', $signed('BID:42*VS:2026000125*RES:FAIL')),
    'forged: another merchant' => $signed('BID:43*VS:2026000125*RES:OK'),
    'signed, unknown order' => $signed('BID:42*VS:9999*RES:OK'),
    'no responseString' => null,
];

foreach ($returns as $label => $responseString) {
    $url = $shop . ($responseString === null ? '' : '?' . http_build_query(['responseString' => $responseString]));
    $body = file_get_contents($url, false, stream_context_create(['http' => ['ignore_errors' => true]]));
    $status = $http_response_header[0] ?? '';
    printf("%-28s %s  %s\n", $label, substr($status, 9, 3), $body);
}
