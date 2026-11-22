<?php

declare(strict_types=1);

namespace Omnipay\Viamo;

use Omnipay\Common\AbstractGateway;
use Omnipay\Viamo\Core\CredentialsParameters;
use Omnipay\Viamo\Message\CompletePurchaseRequest;
use Omnipay\Viamo\Message\PurchaseRequest;
use Omnipay\Viamo\Message\WebhookRequest;

/**
 * Viamo mobile payments gateway.
 */
final class Gateway extends AbstractGateway
{
    use CredentialsParameters;

    #[\Override]
    public function getName(): string
    {
        return 'Viamo Gateway';
    }

    /**
     * @return array{bid: string, key1: string, key2: string, testMode: bool}
     */
    #[\Override]
    public function getDefaultParameters(): array
    {
        return [
            'bid' => '',
            'key1' => '',
            'key2' => '',
            'testMode' => false,
        ];
    }

    /**
     * Builds the signed payment request: a QR code and an app link for the Viamo app.
     *
     * @param array<string, mixed> $parameters
     */
    public function purchase(array $parameters = []): PurchaseRequest
    {
        return new PurchaseRequest($this->httpClient, $this->httpRequest)->initialize($this->requestParameters($parameters));
    }

    /**
     * Verifies the `responseString` that Viamo appends to the return URL.
     *
     * @param array<string, mixed> $parameters
     */
    public function completePurchase(array $parameters = []): CompletePurchaseRequest
    {
        return new CompletePurchaseRequest($this->httpClient, $this->httpRequest)->initialize($this->requestParameters($parameters));
    }

    /**
     * Verifies a webhook notification. Pass the decoded JSON body as the `json` parameter.
     *
     * @param array<string, mixed> $parameters
     */
    public function webhook(array $parameters = []): WebhookRequest
    {
        return new WebhookRequest($this->httpClient, $this->httpRequest)->initialize($this->requestParameters($parameters));
    }

    /**
     * The gateway parameters overridden by the request parameters, like AbstractGateway::createRequest().
     *
     * @param array<string, mixed> $parameters
     * @return array<mixed>
     */
    private function requestParameters(array $parameters): array
    {
        return array_replace($this->getParameters(), $parameters);
    }
}
