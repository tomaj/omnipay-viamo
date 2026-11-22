<?php

declare(strict_types=1);

use Omnipay\Viamo\Gateway;

/** @var Gateway $gateway */
$gateway = require __DIR__ . '/gateway.php';

// The return URL: https://shop.example/viamo/return?responseString=BID:…*VS:…*RES:OK*SIG:…
$result = $gateway->completePurchase()->send();

if ($result->isSuccessful()) {
    $vs = $result->getVs(); // find the order by its variable symbol and mark it paid
    echo "Payment for order {$vs} received.";
} else {
    // RES:FAIL from Viamo, or ERROR when the response could not be verified
    echo 'Payment failed: ' . ($result->getMessage() ?? $result->getRes());
}
