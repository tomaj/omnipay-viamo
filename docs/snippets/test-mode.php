<?php

declare(strict_types=1);

use Omnipay\Viamo\Gateway;

/** @var Gateway $gateway */
$gateway = require __DIR__ . '/gateway.php';
$gateway->setTestMode(true);

$response = $gateway->purchase(['amount' => '1.00', 'currency' => 'EUR', 'vs' => '1'])->send();

echo $response->getAppLink(); // viamo-staging://?requestString=QP:1.0*BID:…*AM:1.00*CC:EUR*VS:1*SIG:…
