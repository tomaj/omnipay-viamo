<?php

namespace Omnipay\Viamo\Message;

use Omnipay\Common\Message\AbstractResponse;

class PurchaseResponse extends AbstractResponse // implements RedirectResponseInterface
{
    /**
     * @return bool
     */
    public function isSuccessful()
    {
        return true;
    }

    /**
     * @return bool
     */
    public function isRedirect()
    {
        return false;
    }

    /**
     * @return mixed
     */
    public function getVs()
    {
        if (is_array($this->data) && isset($this->data['vs'])) {
            return $this->data['vs'];
        }
        return false;
    }
}
