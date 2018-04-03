<?php

namespace Omnipay\Viamo\Message;

use Omnipay\Common\Currency;
use Omnipay\Common\Message\AbstractRequest;

class PurchaseRequest extends AbstractRequest
{
    public function initialize(array $parameters = array())
    {
        parent::initialize($parameters);
        return $this;
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

    public function setVs($value)
    {
        return $this->setParameter('vs', $value);
    }

    public function getVs()
    {
        return $this->getParameter('vs');
    }

    public function getData()
    {
        $this->validate('bid', 'key');
        return [];
    }

    public function sendData($data)
    {
        $data['text'] = "QP:1.0*BID:{$this->getBid()}*AM:{$this->getAmount()}*VS:{$this->getVs()}";
        $data['template'] = 300;
        return $this->response = new PurchaseResponse($this, $data);
    }

    public function getEndpoint()
    {
        return 'https://qr.viamo.sk/api/';
    }
}
