<?php

namespace Omnipay\Viamo\Core;

class ViamoSign
{
    /**
     * @param mixed $input
     * @param mixed $secret hex encoded key
     * @return string
     */
    public function sign($input, $secret)
    {
        $hash = substr(hash('sha256', Str::from($input)), 0, 32);
        $sign = openssl_encrypt((string) hex2bin($hash), 'aes-128-ecb', (string) hex2bin(Str::from($secret)), OPENSSL_RAW_DATA | OPENSSL_ZERO_PADDING);
        return strtoupper(bin2hex((string) $sign));
    }
}
