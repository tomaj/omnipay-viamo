<?php

declare(strict_types=1);

use Omnipay\Viamo\Gateway;

/** @var Gateway $gateway */
$gateway = require __DIR__ . '/gateway.php';
$gateway->setTestMode(true);

$response = $gateway->purchase(['amount' => '1.00', 'currency' => 'EUR', 'vs' => '1'])->send();

echo $response->getAppLink(); // viamo-staging://?requestString=QP%3A1.0%2ABID%3A…%2AAM%3A1.00%2ACC%3AEUR%2AVS%3A1%2ASIG%3A…
