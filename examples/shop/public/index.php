<?php

declare(strict_types=1);

// A minimal shop with the three Viamo endpoints. Run with: php -S 127.0.0.1:8363 public/index.php
//   GET  /checkout?order=…   the payment page: QR code for desktop, app link for phones
//   GET  /viamo/return       where the Viamo app sends the customer back (responseString)
//   POST /viamo/webhook      where Viamo notifies the shop (signed JSON)

use Omnipay\Viamo\Gateway;

require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../Orders.php';

/** @var Gateway $gateway */
$gateway = require __DIR__ . '/../gateway.php';
$orders = new Orders(getenv('ORDERS_FILE') ?: sys_get_temp_dir() . '/viamo-orders.json');
$baseUrl = 'http://' . $_SERVER['HTTP_HOST'];
$log = static fn(string $line) => error_log($line . PHP_EOL, 3, getenv('LOG_FILE') ?: 'php://stderr');

switch ($_SERVER['REQUEST_METHOD'] . ' ' . parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH)) {
    case 'GET /checkout':
        $id = (string) ($_GET['order'] ?? '');
        $order = $orders->find($id);
        if ($order === null || $order['status'] === 'paid') {
            http_response_code(404);
            echo 'No such unpaid order.';
            break;
        }
        $payment = $gateway->purchase([
            'amount' => $order['total'],
            'currency' => $order['currency'],
            'vs' => $id,
            'msg' => 'Order ' . $id,
            'rurl' => $baseUrl . '/viamo/return',
        ])->send();
        $appLink = htmlspecialchars((string) $payment->getAppLink());
        echo <<<HTML
            <h1>Pay {$order['total']} {$order['currency']} for order {$id}</h1>
            <p>Scan the code with the Viamo app:</p>
            <img alt="Viamo QR code" width="320" height="320" src="data:image/png;base64,{$payment->getQrData()}">
            <p>On your phone? <a class="viamo" href="{$appLink}">Open the Viamo app</a></p>
            HTML;
        break;

    case 'GET /viamo/return':
        $result = $gateway->completePurchase()->send();
        $vs = (string) $result->getVs();
        if ($result->isSuccessful() && $orders->find($vs) !== null) {
            $orders->markPaid($vs, 'return URL');
            echo "Thank you! Order {$vs} is paid.";
        } elseif ($result->getRes() === 'FAIL' && $orders->find($vs) !== null) {
            $orders->markFailed($vs);
            echo "The payment of order {$vs} did not go through.";
        } else {
            http_response_code(400);
            $log('return URL rejected: ' . ($result->getMessage() ?? "unknown order {$vs}"));
            echo 'We could not verify the payment result.';
        }
        break;

    case 'POST /viamo/webhook':
        $json = json_decode((string) file_get_contents('php://input'), true);
        $notification = $gateway->webhook(['json' => is_array($json) ? $json : []])->send();
        $vs = (string) $notification->getVs();
        $order = $orders->find($vs);
        header('Content-Type: application/json');
        if (!$notification->isSuccessful() || $order === null || !is_array($json)) {
            http_response_code($notification->getMessage() === 'Invalid result' ? 200 : 400);
            $log('webhook not paid: ' . ($notification->getMessage() ?? "unknown order {$vs}"));
            echo json_encode(['result' => 'not paid']);
        } elseif ($json['payment']['amount'] !== $order['total']) {
            http_response_code(422);
            $log("webhook amount {$json['payment']['amount']} does not match order {$vs}");
            echo json_encode(['result' => 'amount mismatch']);
        } else {
            $changed = $orders->markPaid($vs, 'webhook', $notification->getPaymentId());
            echo json_encode(['result' => $changed ? 'paid' : 'already paid']);
        }
        break;

    default:
        http_response_code(404);
}
