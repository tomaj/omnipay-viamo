<?php

declare(strict_types=1);

namespace Omnipay\Viamo\Core;

/**
 * The merchant credentials shared by the gateway and its requests.
 *
 * @internal
 */
trait CredentialsParameters
{
    /**
     * The merchant (beneficiary) identifier assigned by Viamo.
     */
    public function getBid(): ?string
    {
        return Str::nullable($this->getParameter('bid'));
    }

    public function setBid(?string $value): static
    {
        return $this->setParameter('bid', $value);
    }

    /**
     * The key (32 hexadecimal characters) that signs the payment request and verifies the return URL.
     */
    public function getKey1(): ?string
    {
        return Str::nullable($this->getParameter('key1'));
    }

    public function setKey1(#[\SensitiveParameter] ?string $value): static
    {
        return $this->setParameter('key1', $value);
    }

    /**
     * The key (32 hexadecimal characters) that verifies the webhook notification.
     */
    public function getKey2(): ?string
    {
        return Str::nullable($this->getParameter('key2'));
    }

    public function setKey2(#[\SensitiveParameter] ?string $value): static
    {
        return $this->setParameter('key2', $value);
    }
}
