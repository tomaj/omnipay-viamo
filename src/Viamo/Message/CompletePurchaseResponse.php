<?php

namespace Omnipay\Viamo\Message;

use Omnipay\Common\Message\AbstractResponse;

class CompletePurchaseResponse extends AbstractResponse
{
    /**
     * @return bool
     */
    public function isSuccessful()
    {
        return $this->getRes() === 'OK';
    }

    /**
     * @return mixed
     */
    public function getRes()
    {
        return $this->getValue('RES');
    }

    /**
     * @return mixed
     */
    public function getVs()
    {
        return $this->getValue('VS');
    }

    private function getValue(string $key): mixed
    {
        if (is_array($this->data) && isset($this->data[$key])) {
            return $this->data[$key];
        }
        return null;
    }
}
