<?php

namespace Omnipay\Viamo\Message;

use Omnipay\Common\Message\AbstractResponse;
use Omnipay\Common\Message\RedirectResponseInterface;

class WebhookResponse extends AbstractResponse
{
    public function isSuccessful()
    {
        return $this->data['success'];
    }

    public function isRedirect()
    {
        return false;
    }

    public function errorMessage()
    {
        return $this->data['error'];
    }

    public function getVs()
    {
        if (isset($this->data['vs'])) {
            return $this->data['vs'];
        }
        return false;
    }
}
