<?php

declare(strict_types=1);

// The shop's webhook endpoint: Viamo POSTs a signed JSON notification for every payment.
// Run with: php -S 127.0.0.1:8362 public/index.php

use Omnipay\Viamo\Gateway;

require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../Orders.php';

function respond(int $status, string $result, ?string $reason = null): void
{
    http_response_code($status);
    header('Content-Type: application/json');
    echo json_encode(['result' => $result] + ($reason !== null ? ['reason' => $reason] : []));
    if ($status >= 400) {
        error_log("Viamo webhook rejected ({$status}): {$reason}" . PHP_EOL, 3, getenv('LOG_FILE') ?: 'php://stderr');
    }
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) !== '/viamo/webhook') {
    respond(404, 'not found', 'no such endpoint');
    return;
}

$json = json_decode((string) file_get_contents('php://input'), true);
if (!is_array($json)) {
    respond(400, 'rejected', 'the body is not a JSON object');
    return;
}

/** @var Gateway $gateway */
$gateway = require __DIR__ . '/../gateway.php';
$orders = new Orders(getenv('ORDERS_FILE') ?: sys_get_temp_dir() . '/viamo-orders.json');

// Checks the signature (key2) over rid, vs, e2e, result, amount and id.
$notification = $gateway->webhook(['json' => $json])->send();
$vs = $notification->getVs();
$order = $vs !== null ? $orders->find($vs) : null;

if (!$notification->isSuccessful()) {
    if ($notification->getMessage() === 'Invalid result' && $order !== null) {
        // Correctly signed, but the payment did not succeed.
        $orders->markFailed($vs);
        respond(200, 'payment failed');
    } else {
        respond(400, 'rejected', $notification->getMessage());
    }
    return;
}

if ($order === null) {
    respond(404, 'rejected', "unknown order {$vs}");
    return;
}

// #region amount
// getAmount() is the amount covered by the verified signature, exactly as Viamo sent it.
// Compare it with the order before you ship anything.
$amount = (string) $notification->getAmount();
if (!is_numeric($amount) || number_format((float) $amount, 2, '.', '') !== $order['total']) {
    respond(422, 'rejected', "amount {$amount} does not match order {$vs} ({$order['total']})");
    return;
}
// #endregion amount

// Webhooks can be delivered more than once: markPaid() is idempotent.
$changed = $orders->markPaid($vs, 'webhook', $notification->getPaymentId());
respond(200, $changed ? 'paid' : 'already paid');
