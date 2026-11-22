<?php

namespace Omnipay\Viamo\Message;

use Omnipay\Common\Message\AbstractRequest;
use Omnipay\Viamo\Core\Str;
use Omnipay\Viamo\Core\ViamoSign;

class CompletePurchaseRequest extends AbstractRequest
{
    /**
     * @return array<string, string>
     */
    public function getData()
    {
        $responseString = Str::from($_GET['responseString']);
        $pos = strpos($responseString, '*SIG:');
        $input = substr($responseString, 0, (int) $pos);

        $parts = explode('*', $responseString);
        $inputData = [];
        foreach ($parts as $pairs) {
            [$key, $value] = explode(':', $pairs);
            $inputData[$key] = $value;
        }

        if (Str::from($this->getBid()) !== $inputData['BID']) {
            return [
                'RES' => 'ERROR',
                'vs' => $inputData['VS'],
                'message' => 'Wrong BID',
            ];
        }

        $viamoSign = new ViamoSign();

        if ($viamoSign->sign($input, $this->getKey1()) !== strtoupper($inputData['SIG'])) {
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

    /**
     * @param mixed $data
     * @return CompletePurchaseResponse
     */
    public function sendData($data)
    {
        return $this->response = new CompletePurchaseResponse($this, $data);
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
     * @return mixed
     */
    public function getKey2()
    {
        return $this->getParameter('key2');
    }

    /**
     * @param mixed $value
     * @return $this
     */
    public function setKey2($value)
    {
        return $this->setParameter('key2', $value);
    }
}
