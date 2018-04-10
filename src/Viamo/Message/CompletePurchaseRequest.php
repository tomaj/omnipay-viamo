<?php

namespace Omnipay\Viamo\Message;

use Omnipay\Common\Currency;
use Omnipay\Common\Exception\InvalidRequestException;
use Omnipay\Common\Message\AbstractRequest;
use Omnipay\Viamo\Core\ViamoSign;

class CompletePurchaseRequest extends AbstractRequest
{
    public function getData()
    {
        $responseString = $_GET['responseString'];
        $pos = strpos($responseString, '*SIG:');
        $input = substr($responseString, 0, $pos);

        $parts = explode('*', $responseString);
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

        $viamoSign = new ViamoSign();

        if ($viamoSign->sign($input, $this->getKey1()) != strtoupper($inputData['SIG'])) {
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
