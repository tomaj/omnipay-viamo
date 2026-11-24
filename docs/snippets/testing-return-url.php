<?php

declare(strict_types=1);

use Omnipay\Viamo\Core\ViamoSign;

$key1 = (string) getenv('VIAMO_KEY1'); // your test key1

$fields = 'BID:42*VS:2026000123*RES:OK';
$responseString = $fields . '*SIG:' . new ViamoSign()->sign($fields, $key1);

$url = 'https://shop.example/viamo/return?' . http_build_query(['responseString' => $responseString]);
echo $url;
// open it in the browser, or: file_get_contents($url)
