<?php

namespace Omnipay\Viamo\Message;

use Omnipay\Common\Currency;
use Omnipay\Common\Message\AbstractRequest;

class WebhookRequest extends AbstractRequest
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

    public function getKey1()
    {
        return $this->getParameter('key1');
    }

    public function setKey1($value)
    {
        return $this->setParameter('key1', $value);
    }

    public function setKey2($value)
    {
        return $this->setParameter('key2', $value);
    }

    public function getKey2()
    {
        return $this->getParameter('key2');
    }

    public function setJson($json)
    {
        return $this->setParameter('json', $json);
    }

    public function getJson()
    {
        return $this->getParameter('json');
    }

    public function getData()
    {
        $this->validate('bid', 'key2', 'json');
        return [];
    }

    public function sendData($data)
    {
        $k2 = $this->getKey2();
        $json = $this->getParameter('json');

        if (isset($json['payment'])) {
            $payment = $json['payment'];
        } else {
            return $this->response = new WebhookResponse($this, ['success' => false, 'error' => 'Missing payment data']);
        }
        if (!isset($payment['result']) || !isset($payment['amount']) || !isset($payment['id'])) {
            return $this->response = new WebhookResponse($this, ['success' => false, 'error' => 'Missing payment parameter']);
        }
        if (!isset($json['signature']) || !isset($json['signature']['sign'])) {
            return $this->response = new WebhookResponse($this, ['success' => false, 'error' => 'Missing signature']);
        }

        $textToSign = "";
        if (!empty($payment['rid'])) {
            $textToSign .= $payment['rid'];
        }
        if (!empty($payment['vs'])) {
            $textToSign .= $payment['vs'];
        }
        if (!empty($payment['e2e'])) {
            $textToSign .= $payment['e2e'];
        }
        $textToSign .= $payment['result'];
        $textToSign .= $payment['amount'];
        $textToSign .= $payment['id'];

        $hash = hash('sha256', $textToSign, true);
        $trimmedHash = substr($hash, 0, 16);

        $signed = openssl_encrypt($trimmedHash, 'aes-128-ecb', hex2bin($k2), OPENSSL_RAW_DATA | OPENSSL_ZERO_PADDING);

        if (strtoupper(bin2hex($signed)) === strtoupper($json['signature']['sign'])) {
            return $this->response = new WebhookResponse($this, ['success' => true, 'vs' => $payment['vs']]);
        }

        return $this->response = new WebhookResponse($this, ['success' => false, 'error' => 'Wrong signature', 'vs' => $payment['vs']]);
    }

}