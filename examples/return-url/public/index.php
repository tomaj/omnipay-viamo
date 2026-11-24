<?php

declare(strict_types=1);

// The shop's return URL: https://shop.example/viamo/return?responseString=BID:…*VS:…*RES:…*SIG:…
// The Viamo app sends the customer here after the payment. Run with: php -S 127.0.0.1:8361 public/index.php

use Omnipay\Viamo\Gateway;

require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../Orders.php';

if (parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) !== '/viamo/return') {
    http_response_code(404);
    return;
}

/** @var Gateway $gateway */
$gateway = require __DIR__ . '/../gateway.php';
$orders = new Orders(getenv('ORDERS_FILE') ?: sys_get_temp_dir() . '/viamo-orders.json');

// Reads responseString from the query, checks BID and the signature (key1).
$result = $gateway->completePurchase()->send();
$vs = $result->getVs();
$order = $vs !== null ? $orders->find($vs) : null;

if ($result->isSuccessful() && $order !== null) {
    // Signed by Viamo with RES:OK for this variable symbol. The webhook may have marked it already.
    $orders->markPaid($vs, 'return URL');
    echo "Thank you! Order {$vs} is paid.";
} elseif ($result->getRes() === 'FAIL' && $order !== null) {
    // A correctly signed failure: the customer cancelled or the payment was declined.
    $orders->markFailed($vs);
    echo "The payment of order {$vs} did not go through. You can try again.";
} else {
    // ERROR (wrong signature, other merchant, missing fields) or an unknown order: never trust it.
    http_response_code(400);
    error_log('Viamo return URL rejected: ' . ($result->getMessage() ?? 'unknown order ' . $vs) . PHP_EOL, 3, getenv('LOG_FILE') ?: 'php://stderr');
    echo 'We could not verify the payment result. If you paid, the order is updated as soon as Viamo notifies us.';
}
