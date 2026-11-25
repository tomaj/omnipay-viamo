<?php

declare(strict_types=1);

use Omnipay\Viamo\Core\ViamoSign;
use Omnipay\Viamo\Gateway;
use Symfony\Component\HttpFoundation\Request;

$key1 = (string) getenv('VIAMO_KEY1');
$fields = 'BID:42*VS:2026000123*RES:OK';

// The gateway reads the return URL from this request instead of the globals.
$gateway = new Gateway(null, new Request(['responseString' => $fields . '*SIG:' . new ViamoSign()->sign($fields, $key1)]));
$gateway->initialize(['bid' => '42', 'key1' => $key1, 'key2' => (string) getenv('VIAMO_KEY2')]);

$result = $gateway->completePurchase()->send();
assert($result->isSuccessful() && $result->getVs() === '2026000123');

return $result;
