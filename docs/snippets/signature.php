<?php

declare(strict_types=1);

use Omnipay\Viamo\Core\ViamoSign;

$sign = new ViamoSign();

$signature = $sign->sign('BID:42*VS:777*RES:OK', '000102030405060708090a0b0c0d0e0f');
echo $signature; // 04B15D742A42AD489F05F2174C32DBBD

// Constant-time comparison, hexadecimal case does not matter
$valid = $sign->verify('BID:42*VS:777*RES:OK', '000102030405060708090a0b0c0d0e0f', strtolower($signature));
