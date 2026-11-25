<?php

declare(strict_types=1);

use Omnipay\Viamo\Core\ViamoSign;

$key2 = (string) getenv('VIAMO_KEY2'); // your test key2

$payment = ['vs' => '2026000123', 'result' => 'OK', 'amount' => '24.90', 'id' => 555001];
$text = $payment['vs'] . $payment['result'] . $payment['amount'] . $payment['id'];

$body = json_encode([
    'notificationId' => 'N-1001',
    'payment' => $payment,
    'signature' => ['sign' => new ViamoSign()->sign($text, $key2)],
], JSON_THROW_ON_ERROR);
echo $body;

// POST it to your endpoint:
// curl -X POST -H 'Content-Type: application/json' --data "$body" https://shop.example/viamo/webhook
