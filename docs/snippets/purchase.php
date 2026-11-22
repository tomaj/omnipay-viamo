<?php

declare(strict_types=1);

use Omnipay\Viamo\Gateway;

/** @var Gateway $gateway */
$gateway = require __DIR__ . '/gateway.php';

$response = $gateway->purchase([
    'amount' => '10.00',
    'currency' => 'EUR',
    'vs' => '2026000123',                           // variable symbol of the order
    'msg' => 'Order 2026000123',                    // optional message for the payer
    'rurl' => 'https://shop.example/viamo/return',  // where the app returns the customer
])->send();

// Desktop: show the QR code to scan with the Viamo app
echo '<img alt="Viamo QR code" src="data:image/png;base64,' . $response->getQrData() . '">';

// Mobile: open the payment in the Viamo app directly
echo '<a href="' . htmlspecialchars((string) $response->getAppLink()) . '">Pay with Viamo</a>';
