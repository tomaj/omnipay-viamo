<?php

declare(strict_types=1);

use Omnipay\Viamo\Gateway;

/** @var Gateway $gateway */
$gateway = require __DIR__ . '/gateway.php';

$json = json_decode((string) file_get_contents('php://input'), true);
if (!is_array($json)) {
    http_response_code(400);
    return;
}

$notification = $gateway->webhook(['json' => $json])->send();

if ($notification->isSuccessful()) {
    // Correctly signed and paid: find the order by getVs(), check the amount, mark it paid.
    // Make this idempotent: the same payment can be notified more than once.
    $vs = $notification->getVs();
    $amount = $notification->getAmount();  // signed amount as Viamo sent it, e.g. "24.90"
    $paymentId = $notification->getPaymentId();
} else {
    error_log('Viamo webhook rejected: ' . $notification->errorMessage());
}
