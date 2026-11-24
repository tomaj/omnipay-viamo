<?php

declare(strict_types=1);

// Plays the customer's phone and Viamo: opens the shop's checkout page, follows the app link,
// checks the payment request's signature the way Viamo does, "pays", sends the signed webhook
// and returns the customer to the shop with a signed responseString.

use Omnipay\Viamo\Core\ViamoSign;

require __DIR__ . '/vendor/autoload.php';

$shop = 'http://127.0.0.1:8363';
$bid = getenv('VIAMO_BID') ?: '42';
$key1 = getenv('VIAMO_KEY1') ?: '000102030405060708090a0b0c0d0e0f';
$key2 = getenv('VIAMO_KEY2') ?: 'F0E1D2C3B4A5968778695A4B3C2D1E0F';
$sign = new ViamoSign();
$paymentId = 900000;

/** @return array{int, string} HTTP status and body */
function http(string $method, string $url, ?string $json = null): array
{
    $options = ['method' => $method, 'ignore_errors' => true];
    if ($json !== null) {
        $options += ['header' => "Content-Type: application/json\r\n", 'content' => $json];
    }
    $body = (string) file_get_contents($url, false, stream_context_create(['http' => $options]));

    return [(int) substr($http_response_header[0] ?? '', 9, 3), $body];
}

/** Parses KEY:value*KEY:value*…*SIG:… (a value may contain ':'). */
function fields(string $requestString): array
{
    $fields = [];
    foreach (explode('*', $requestString) as $pair) {
        [$key, $value] = explode(':', $pair, 2) + [1 => ''];
        $fields[$key] = $value;
    }

    return $fields;
}

$customers = [
    ['order' => '2026000123', 'action' => 'pay'],
    ['order' => '2026000124', 'action' => 'cancel'],
    ['order' => '2026000125', 'action' => 'tamper'],
];

foreach ($customers as ['order' => $order, 'action' => $action]) {
    echo "== order {$order}: customer will {$action}", PHP_EOL;

    [$status, $page] = http('GET', "{$shop}/checkout?order={$order}");
    preg_match('~<h1>(.*?)</h1>~', $page, $title);
    preg_match('~<a class="viamo" href="([^"]+)"~', $page, $link);
    echo "shop checkout page: {$status} \"{$title[1]}\", QR code ", str_contains($page, 'data:image/png;base64,') ? 'shown' : 'missing', PHP_EOL;

    $appLink = html_entity_decode($link[1]);
    $requestString = substr($appLink, strlen('viamo://?requestString='));
    if ($action === 'tamper') {
        // someone edits the amount in the link before the app opens it
        $requestString = preg_replace('/\*AM:[^*]+/', '*AM:1.00', $requestString);
    }

    // Viamo checks the request: everything before the last *SIG: signed with the merchant's key1.
    $position = strrpos($requestString, '*SIG:');
    $fields = fields($requestString);
    if (!$sign->verify(substr($requestString, 0, $position), $key1, $fields['SIG'])) {
        echo 'viamo app: request signature invalid, payment refused', PHP_EOL, PHP_EOL;
        continue;
    }
    echo "viamo app: signature ok, pay {$fields['AM']} {$fields['CC']} to merchant {$fields['BID']}, VS {$fields['VS']}, \"{$fields['MSG']}\"", PHP_EOL;

    $result = $action === 'pay' ? 'OK' : 'FAIL';
    if ($action === 'pay') {
        $payment = ['vs' => $fields['VS'], 'result' => $result, 'amount' => $fields['AM'], 'id' => ++$paymentId];
        $text = $payment['vs'] . $payment['result'] . $payment['amount'] . $payment['id'];
        $body = json_encode(['notificationId' => "N-{$paymentId}", 'payment' => $payment, 'signature' => ['sign' => $sign->sign($text, $key2)]]);
        [$status, $answer] = http('POST', "{$shop}/viamo/webhook", $body);
        echo "viamo webhook: {$status} {$answer}", PHP_EOL;
    }

    // Back to the shop: RURL (%2A was the escaped *) with a signed responseString.
    $response = "BID:{$bid}*VS:{$fields['VS']}*RES:{$result}";
    $returnUrl = str_replace('%2A', '*', $fields['RURL']) . '?' . http_build_query(['responseString' => $response . '*SIG:' . $sign->sign($response, $key1)]);
    [$status, $page] = http('GET', $returnUrl);
    echo "return URL: {$status} {$page}", PHP_EOL, PHP_EOL;
}
