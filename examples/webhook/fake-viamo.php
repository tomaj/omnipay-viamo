<?php

declare(strict_types=1);

// Plays Viamo: POSTs webhook notifications signed the way Viamo signs them (ViamoSign with key2),
// plus a few forged ones, to the shop's webhook endpoint.

use Omnipay\Viamo\Core\ViamoSign;

require __DIR__ . '/vendor/autoload.php';

$endpoint = 'http://127.0.0.1:8362/viamo/webhook';
$key2 = getenv('VIAMO_KEY2') ?: 'F0E1D2C3B4A5968778695A4B3C2D1E0F';

/**
 * The signed text is rid, vs and e2e (each only when not empty), then result, amount and id,
 * concatenated without separators.
 *
 * @param array<string, string|int> $payment
 */
function notification(string $id, array $payment, string $key2): array
{
    $text = '';
    foreach (['rid', 'vs', 'e2e'] as $optional) {
        $text .= (string) ($payment[$optional] ?? '');
    }
    $text .= $payment['result'] . $payment['amount'] . $payment['id'];

    return ['notificationId' => $id, 'payment' => $payment, 'signature' => ['sign' => new ViamoSign()->sign($text, $key2)]];
}

$paid = notification('N-1001', ['rid' => 'R-77', 'vs' => '2026000123', 'e2e' => 'E2E-2026000123', 'result' => 'OK', 'amount' => '24.90', 'id' => 555001], $key2);
$tampered = notification('N-1004', ['vs' => '2026000125', 'result' => 'OK', 'amount' => '0.01', 'id' => 555003], $key2);
$tampered['payment']['amount'] = '120.00';
$unsigned = $paid;
unset($unsigned['signature']);

$notifications = [
    'paid' => $paid,
    'same notification again' => $paid,
    'payment failed' => notification('N-1002', ['vs' => '2026000124', 'result' => 'FAIL', 'amount' => '9.90', 'id' => 555002], $key2),
    'forged: amount changed' => $tampered,
    'signed, wrong amount' => notification('N-1005', ['vs' => '2026000125', 'result' => 'OK', 'amount' => '1.20', 'id' => 555004], $key2),
    'signed, unknown order' => notification('N-1006', ['vs' => '9999', 'result' => 'OK', 'amount' => '5.00', 'id' => 555005], $key2),
    'no signature' => $unsigned,
    'not JSON' => 'hello',
];

foreach ($notifications as $label => $body) {
    $content = is_array($body) ? json_encode($body, JSON_THROW_ON_ERROR) : $body;
    $context = stream_context_create(['http' => [
        'method' => 'POST',
        'header' => "Content-Type: application/json\r\n",
        'content' => $content,
        'ignore_errors' => true,
    ]]);
    $response = file_get_contents($endpoint, false, $context);
    printf("%-26s %s  %s\n", $label, substr($http_response_header[0] ?? '', 9, 3), $response);
}
