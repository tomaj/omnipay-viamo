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
            'key' => ''
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

    public function getKey()
    {
        return $this->getParameter('key');
    }

    public function setKey($value)
    {
        return $this->setParameter('key', $value);
    }

    public function purchase(array $parameters = array())
    {
        return $this->createRequest(\Omnipay\Viamo\Message\PurchaseRequest::class, $parameters);
    }

    public function completePurchase(array $parameters = array())
    {
        return $this->createRequest(\Omnipay\Viamo\Message\CompletePurchaseRequest::class, $parameters);
    }
}
