<?php

declare(strict_types=1);

use Omnipay\Omnipay;
use Omnipay\Viamo\Gateway;

// Test credentials (the same ones the library's own tests use). In your shop, read the real
// ones from the environment or a secret store and never commit them.
$gateway = Omnipay::create('Viamo');
assert($gateway instanceof Gateway);

return $gateway->initialize([
    'bid' => getenv('VIAMO_BID') ?: '42',
    'key1' => getenv('VIAMO_KEY1') ?: '000102030405060708090a0b0c0d0e0f', // signs the request, verifies the return URL
    'key2' => getenv('VIAMO_KEY2') ?: 'F0E1D2C3B4A5968778695A4B3C2D1E0F', // verifies the webhook
    'testMode' => getenv('VIAMO_TEST_MODE') === '1',
]);
