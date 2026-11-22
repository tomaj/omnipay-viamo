<?php

namespace Omnipay\Viamo\Message;

use Omnipay\Common\Message\AbstractResponse;

class WebhookResponse extends AbstractResponse
{
    /**
     * @return bool
     */
    public function isSuccessful()
    {
        return $this->getValue('success') === true;
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
    public function errorMessage()
    {
        return $this->getValue('error');
    }

    /**
     * @return mixed
     */
    public function getVs()
    {
        return $this->getValue('vs') ?? false;
    }

    /**
     * @return mixed
     */
    public function getPaymentId()
    {
        return $this->getValue('payment_id') ?? false;
    }

    /**
     * @return mixed
     */
    public function getNotificationId()
    {
        return $this->getValue('notification_id') ?? false;
    }

    private function getValue(string $key): mixed
    {
        if (is_array($this->data) && isset($this->data[$key])) {
            return $this->data[$key];
        }
        return null;
    }
}
