<?php

declare(strict_types=1);

namespace Omnipay\Viamo\Message;

use Omnipay\Common\Message\AbstractResponse;
use Omnipay\Viamo\Core\Str;

/**
 * The verified result of the return URL.
 */
final class CompletePurchaseResponse extends AbstractResponse
{
    /**
     * True only for a correctly signed result of this merchant with `RES:OK`.
     */
    #[\Override]
    public function isSuccessful(): bool
    {
        return $this->getRes() === 'OK';
    }

    /**
     * `OK`, `FAIL` (as sent by Viamo), or `ERROR` when the response could not be verified.
     */
    public function getRes(): ?string
    {
        return $this->getValue('RES');
    }

    public function getVs(): ?string
    {
        return $this->getValue('VS');
    }

    /**
     * Why the response was rejected (`Wrong BID`, `Wrong sign`, …), or null.
     */
    #[\Override]
    public function getMessage(): ?string
    {
        return $this->getValue('message');
    }

    private function getValue(string $key): ?string
    {
        return is_array($this->data) ? Str::nullable($this->data[$key] ?? null) : null;
    }
}
