<?php

namespace Omnipay\Viamo\Core;

class ViamoSign
{
    public function sign($input, $secret)
    {
        $hash = substr(hash('sha256', $input), 0, 32);
        $sign = openssl_encrypt(hex2bin($hash), 'aes-128-ecb', hex2bin($secret), OPENSSL_RAW_DATA | OPENSSL_ZERO_PADDING);
        return strtoupper(bin2hex($sign));
    }
}
