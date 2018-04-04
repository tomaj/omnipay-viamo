<?php

namespace Omnipay\Viamo\Message;

use Omnipay\Common\Currency;
use Omnipay\Common\Exception\InvalidRequestException;
use Omnipay\Common\Message\AbstractRequest;

class CompletePurchaseRequest extends AbstractRequest
{
    public function getData()
    {
        $requestString = $_GET['requestString'];
        $pos = strpos($requestString, '*SIG:');
        $input = substr($requestString, 0, $pos);

        $parts = explode('*', $requestString);
        $inputData = [];
        foreach ($parts as $pairs) {
            list($key, $value) = explode(':', $pairs);
            $inputData[$key] = $value;
        }

        if ($this->getBid() != $inputData['BID']) {
            return [
                'RES' => 'ERROR',
                'vs' => $inputData['VS'],
                'message' => 'Wrong BID',
            ];
        }

        $k1 = $this->getKey1();
        $sign = openssl_encrypt($input, 'aes-128-ecb', hex2bin($k1), OPENSSL_RAW_DATA | OPENSSL_ZERO_PADDING);

        if ($sign != $inputData['SIG']) {
            return [
                'RES' => 'ERROR',
                'vs' => $inputData['VS'],
                'message' => 'Wrong sign',
            ];
        }

        return [
            'RES' => $inputData['RES'],
            'vs' => $inputData['VS'],
        ];
    }

    public function sendData($data)
    {
        return $this->response = new CompletePurchaseResponse($this, $data);
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
}
