<?php

namespace Omnipay\Viamo\Message;

use Omnipay\Common\Message\AbstractResponse;

class CompletePurchaseResponse extends AbstractResponse
{
    public function isSuccessful()
    {
        return $this->getRes() == 'OK';
    }

    public function getRes()
    {
        if (isset($this->data['RES'])) {
            return $this->data['RES'];
        }
        return null;
    }

    public function getVs()
    {
        if (isset($this->data['VS'])) {
            return $this->data['VS'];
        }
        return null;
    }
}
