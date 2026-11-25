<?php

declare(strict_types=1);

use Omnipay\Common\Exception\InvalidRequestException;
use Omnipay\Viamo\Exception\ViamoException;
use Omnipay\Viamo\Gateway;

/** @var Gateway $gateway */
$gateway = require __DIR__ . '/gateway.php';

try {
    $response = $gateway->purchase(['amount' => '10.00', 'key1' => 'not-a-key'])->send();
} catch (ViamoException $e) {
    // Omnipay\Viamo\Exception\InvalidKeyException: key1/key2 are not 32 hex characters
    error_log($e->getMessage());
} catch (InvalidRequestException $e) {
    // a required parameter (bid, key1, amount) is missing or empty, a payment symbol is not
    // up to 10 digits, or a value contains the field separator *
    error_log($e->getMessage());
}
