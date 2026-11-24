<?php

declare(strict_types=1);

// Checkout: sign one order as a Viamo payment request and show it as a QR code (desktop)
// and as an app link (phone). Nothing is sent anywhere: purchase() only signs and renders.

use Omnipay\Viamo\Gateway;

require __DIR__ . '/vendor/autoload.php';

// #region purchase
/** @var Gateway $gateway */
$gateway = require __DIR__ . '/gateway.php';

$order = ['id' => '2026000123', 'total' => '24.90', 'currency' => 'EUR'];

$response = $gateway->purchase([
    'amount' => $order['total'],
    'currency' => $order['currency'],
    'vs' => $order['id'],                                        // variable symbol: identifies the order later
    'msg' => 'Order ' . $order['id'],                            // shown to the payer in the app
    'rurl' => 'https://shop.example/viamo/return?order=' . $order['id'],
])->send();
// #endregion purchase

echo 'successful: ', var_export($response->isSuccessful(), true), ', redirect: ', var_export($response->isRedirect(), true), PHP_EOL;
echo 'variable symbol: ', $response->getVs(), PHP_EOL;

// The QR code encodes the signed request string; Viamo's QR API renders the same string.
$query = [];
parse_str((string) parse_url((string) $response->getQrUrl(), PHP_URL_QUERY), $query);
echo 'request string: ', $query['text'], PHP_EOL;
echo 'QR API URL: ', $response->getQrUrl(), PHP_EOL;

// getQrData() is a base64 PNG: embed it as a data URI, or save it as a file.
$png = base64_decode((string) $response->getQrData(), true);
$size = getimagesizefromstring((string) $png);
echo 'QR code: ', $size['mime'] ?? '?', ', ', $size[0] ?? 0, 'x', $size[1] ?? 0, ' px', PHP_EOL;
file_put_contents(sys_get_temp_dir() . '/viamo-qr-' . $order['id'] . '.png', $png);
echo '<img alt="Pay with Viamo" src="data:image/png;base64,', substr((string) $response->getQrData(), 0, 24), '…">', PHP_EOL;

// On a phone, link straight to the app. The app link also carries the return URL (RURL),
// with every * escaped as %2A, and is signed separately.
echo 'app link: ', $response->getAppLink(), PHP_EOL;

// Test mode only changes the app link scheme to Viamo's staging app.
$gateway->setTestMode(true);
$staging = $gateway->purchase(['amount' => $order['total'], 'currency' => $order['currency'], 'vs' => $order['id']])->send();
echo 'test mode app link: ', $staging->getAppLink(), PHP_EOL;
