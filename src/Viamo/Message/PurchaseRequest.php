<?php

namespace Omnipay\Viamo\Message;

use Endroid\QrCode\Writer\PngWriter;
use Omnipay\Common\Message\AbstractRequest;
use Omnipay\Viamo\Core\ViamoSign;
use Endroid\QrCode\QrCode;

class PurchaseRequest extends AbstractRequest
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

    public function getKey2()
    {
        return $this->getParameter('key2');
    }

    public function setKey2($value)
    {
        return $this->setParameter('key2', $value);
    }

    public function getRurl()
    {
        return $this->getParameter('rurl');
    }

    public function setRurl($value)
    {
        return $this->setParameter('rurl', $value);
    }

    public function setVs($value)
    {
        return $this->setParameter('vs', $value);
    }

    public function getVs()
    {
        return $this->getParameter('vs');
    }

    public function setCs($value)
    {
        return $this->setParameter('cs', $value);
    }

    public function getCs()
    {
        return $this->getParameter('cs');
    }

    public function setSs($value)
    {
        return $this->setParameter('ss', $value);
    }

    public function getSs()
    {
        return $this->getParameter('ss');
    }

    public function setMsg($value)
    {
        return $this->setParameter('msg', $value);
    }

    public function getMsg()
    {
        return $this->getParameter('msg');
    }

    public function setDt($value)
    {
        return $this->setParameter('dt', $value);
    }

    public function getDt()
    {
        return $this->getParameter('dt');
    }

    public function getTestMode()
    {
        return $this->getParameter('testMode');
    }

    public function setTestMode($value)
    {
        return $this->setParameter('testMode', $value);
    }

    public function setRpt($value)
    {
        return $this->setParameter('rpt', $value);
    }

    public function getRpt()
    {
        return $this->getParameter('rpt');
    }

    public function getData()
    {
        $this->validate('bid', 'key1', 'key2');
        return [];
    }

    public function sendData($data)
    {
        $params = [];

        $params['QP'] = '1.0';
        $params['BID'] = $this->getBid();
        $params['AM'] = $this->getAmount();
        if ($this->getCurrency()) {
            $params['CC'] = $this->getCurrency();
        }
        if ($this->getVs()) {
            $params['VS'] = $this->getVs();
        }
        if ($this->getCs()) {
            $params['CS'] = $this->getCs();
        }
        if ($this->getSs()) {
            $params['SS'] = $this->getSs();
        }
        if ($this->getRpt()) {
            $params['RPT'] = intval($this->getRpt());
        }
        if ($this->getMsg()) {
            $params['MSG'] = $this->getMsg();
        }

        $requestString = $this->prepareRequestString($params);

        $data['vs'] = $this->getVs();
        $data['template'] = 300;
        $data['qr_url'] = $this->getQrEndpoint() . '?text=' . $requestString. '&template=' . $data['template'];
        $qrCode = new QrCode($requestString);
        $qrCode->setSize(300);

        $writer = new PngWriter();
        $data['qr_data'] = base64_encode($writer->write($qrCode)->getString());

        if ($this->getRurl()) {
            $params['RURL'] = str_replace('*', '%2A', $this->getRurl());
        }

        $appRequestString = $this->prepareRequestString($params);

        $app = 'viamo';
        if ($this->getTestMode()) {
            $app = 'viamo-staging';
        }
        if ($this->getMsg()) {
            $params['MSG'] = $this->getMsg();
        }
        $data['app_link'] = $app . '://?requestString=' . $appRequestString;

        return $this->response = new PurchaseResponse($this, $data);
    }

    private function prepareRequestString($params)
    {
        $paramsStrings = [];
        foreach ($params as $key => $value) {
            $paramsStrings[] = "{$key}:{$value}";
        }

        $requestString = implode('*', $paramsStrings);

        $sign = new ViamoSign();

        $requestString .= '*SIG:' . $sign->sign($requestString, $this->getKey1());

        return $requestString;
    }

    public function getQrEndpoint()
    {
        return 'https://qr.viamo.sk/api/';
    }
}
