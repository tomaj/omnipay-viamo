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
    // a required parameter (bid, key1, key2) is missing
    error_log($e->getMessage());
}
