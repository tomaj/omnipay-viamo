<?php

namespace Omnipay\Viamo;

use Omnipay\Common\AbstractGateway;
use Omnipay\Common\Message\AbstractRequest;
use Omnipay\Viamo\Message\CompletePurchaseRequest;
use Omnipay\Viamo\Message\PurchaseRequest;
use Omnipay\Viamo\Message\WebhookRequest;

class Gateway extends AbstractGateway
{
    /**
     * @return string
     */
    public function getName()
    {
        return 'Viamo Gateway';
    }

    /**
     * @return array<string, mixed>
     */
    public function getDefaultParameters()
    {
        return [
            'bid' => '',
            'key1' => '',
            'key2' => '',
        ];
    }

    /**
     * @return mixed
     */
    public function getBid()
    {
        return $this->getParameter('bid');
    }

    /**
     * @param mixed $value
     * @return $this
     */
    public function setBid($value)
    {
        return $this->setParameter('bid', $value);
    }

    /**
     * @return mixed
     */
    public function getKey1()
    {
        return $this->getParameter('key1');
    }

    /**
     * @param mixed $value
     * @return $this
     */
    public function setKey1($value)
    {
        return $this->setParameter('key1', $value);
    }

    /**
     * @return mixed
     */
    public function getKey2()
    {
        return $this->getParameter('key2');
    }

    /**
     * @param mixed $value
     * @return $this
     */
    public function setKey2($value)
    {
        return $this->setParameter('key2', $value);
    }

    /**
     * @param array<string, mixed> $parameters
     * @return AbstractRequest
     */
    public function purchase(array $parameters = [])
    {
        return $this->createRequest(PurchaseRequest::class, $parameters);
    }

    /**
     * @param array<string, mixed> $parameters
     * @return AbstractRequest
     */
    public function completePurchase(array $parameters = [])
    {
        return $this->createRequest(CompletePurchaseRequest::class, $parameters);
    }

    /**
     * @param array<string, mixed> $json
     * @return AbstractRequest
     */
    public function webhook(array $json = [])
    {
        return $this->createRequest(WebhookRequest::class, $json);
    }
}
