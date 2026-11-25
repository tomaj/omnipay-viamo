<?php

declare(strict_types=1);

use Omnipay\Omnipay;
use Omnipay\Viamo\Gateway;

$gateway = Omnipay::create('Viamo');
assert($gateway instanceof Gateway);

$gateway->initialize([
    'bid' => (string) getenv('VIAMO_BID'),   // merchant identifier from Viamo
    'key1' => (string) getenv('VIAMO_KEY1'), // 32 hex characters: signs the request, verifies the return URL
    'key2' => (string) getenv('VIAMO_KEY2'), // 32 hex characters: verifies the webhook
    'testMode' => getenv('VIAMO_TEST_MODE') === '1',
]);

return $gateway;
