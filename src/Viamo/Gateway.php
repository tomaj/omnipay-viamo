<?php

namespace Omnipay\Viamo;

use Omnipay\Common\AbstractGateway;

class Gateway extends AbstractGateway
{
    public function getName()
    {
        return 'Viamo Gateway';
    }

    public function getDefaultParameters()
    {
        return [
            'bid' => '',
            'key1' => '',
            'key2' => '',
        ];
    }

    public function getBid()
    {
        return $this->getParameter('bid');
    }

    public function setBid($value)
    {
        return $this->setParameter('bid', $value);
    }

    public function getKey1()
    {
        return $this->getParameter('key1');
    }

    public function setKey1($value)
    {
        return $this->setParameter('key1', $value);
    }

    public function getKey2()
    {
        return $this->getParameter('key2');
    }

    public function setKey2($value)
    {
        return $this->setParameter('key2', $value);
    }
    
    public function purchase(array $parameters = array())
    {
        return $this->createRequest(\Omnipay\Viamo\Message\PurchaseRequest::class, $parameters);
    }

    public function completePurchase(array $parameters = array())
    {
        return $this->createRequest(\Omnipay\Viamo\Message\CompletePurchaseRequest::class, $parameters);
    }

    public function webhook(array $json = array())
    {
        return $this->createRequest(\Omnipay\Viamo\Message\WebhookRequest::class, $json);
    }
}
