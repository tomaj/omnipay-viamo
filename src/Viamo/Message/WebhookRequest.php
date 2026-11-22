<?php

namespace Omnipay\Viamo\Message;

use Omnipay\Common\Message\AbstractRequest;
use Omnipay\Viamo\Core\Str;
use Omnipay\Viamo\Core\ViamoSign;

class WebhookRequest extends AbstractRequest
{
    /**
     * @param array<mixed> $parameters
     * @return $this
     */
    public function initialize(array $parameters = [])
    {
        parent::initialize($parameters);
        return $this;
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
     * @param mixed $value
     * @return $this
     */
    public function setKey2($value)
    {
        return $this->setParameter('key2', $value);
    }

    /**
     * @return mixed
     */
    public function getKey2()
    {
        return $this->getParameter('key2');
    }

    /**
     * @param mixed $json
     * @return $this
     */
    public function setJson($json)
    {
        return $this->setParameter('json', $json);
    }

    /**
     * @return mixed
     */
    public function getJson()
    {
        return $this->getParameter('json');
    }

    /**
     * @return array<string, mixed>
     */
    public function getData()
    {
        $this->validate('bid', 'key2', 'json');
        return [];
    }

    /**
     * @param mixed $data
     * @return WebhookResponse
     */
    public function sendData($data)
    {
        $json = $this->getParameter('json');

        if (is_array($json) && isset($json['payment'])) {
            $payment = $json['payment'];
        } else {
            return $this->response = new WebhookResponse($this, ['success' => false, 'error' => 'Missing payment data']);
        }
        if (!is_array($payment) || !isset($payment['result']) || !isset($payment['amount']) || !isset($payment['id'])) {
            return $this->response = new WebhookResponse($this, ['success' => false, 'error' => 'Missing payment parameter']);
        }
        if (!isset($json['signature']) || !is_array($json['signature']) || !isset($json['signature']['sign'])) {
            return $this->response = new WebhookResponse($this, ['success' => false, 'error' => 'Missing signature']);
        }

        $notificationId = $json['notificationId'] ?? null;

        $textToSign = '';
        foreach (['rid', 'vs', 'e2e'] as $optional) {
            if (isset($payment[$optional]) && (bool) $payment[$optional]) {
                $textToSign .= Str::from($payment[$optional]);
            }
        }
        $textToSign .= Str::from($payment['result']);
        $textToSign .= Str::from($payment['amount']);
        $textToSign .= Str::from($payment['id']);

        $viamoSign = new ViamoSign();

        if ($viamoSign->sign($textToSign, $this->getKey2()) !== strtoupper(Str::from($json['signature']['sign']))) {
            return $this->response = new WebhookResponse($this, ['success' => false, 'error' => 'Wrong signature', 'vs' => $payment['vs'], 'payment_id' => $payment['id'], 'notification_id' => $notificationId]);
        }
        if ($payment['result'] !== 'OK') {
            return $this->response = new WebhookResponse($this, ['success' => false, 'error' => 'Invalid result', 'vs' => $payment['vs'], 'payment_id' => $payment['id'], 'notification_id' => $notificationId]);
        }

        return $this->response = new WebhookResponse($this, ['success' => true, 'vs' => $payment['vs'], 'notification_id' => $notificationId, 'payment_id' => $payment['id']]);
    }
}
