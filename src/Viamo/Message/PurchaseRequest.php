<?php

namespace Omnipay\Viamo\Message;

use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;
use Omnipay\Common\Message\AbstractRequest;
use Omnipay\Viamo\Core\Str;
use Omnipay\Viamo\Core\ViamoSign;

class PurchaseRequest extends AbstractRequest
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

    /**
     * @return mixed
     */
    public function getRurl()
    {
        return $this->getParameter('rurl');
    }

    /**
     * @param mixed $value
     * @return $this
     */
    public function setRurl($value)
    {
        return $this->setParameter('rurl', $value);
    }

    /**
     * @param mixed $value
     * @return $this
     */
    public function setVs($value)
    {
        return $this->setParameter('vs', $value);
    }

    /**
     * @return mixed
     */
    public function getVs()
    {
        return $this->getParameter('vs');
    }

    /**
     * @param mixed $value
     * @return $this
     */
    public function setCs($value)
    {
        return $this->setParameter('cs', $value);
    }

    /**
     * @return mixed
     */
    public function getCs()
    {
        return $this->getParameter('cs');
    }

    /**
     * @param mixed $value
     * @return $this
     */
    public function setSs($value)
    {
        return $this->setParameter('ss', $value);
    }

    /**
     * @return mixed
     */
    public function getSs()
    {
        return $this->getParameter('ss');
    }

    /**
     * @param mixed $value
     * @return $this
     */
    public function setMsg($value)
    {
        return $this->setParameter('msg', $value);
    }

    /**
     * @return mixed
     */
    public function getMsg()
    {
        return $this->getParameter('msg');
    }

    /**
     * @param mixed $value
     * @return $this
     */
    public function setDt($value)
    {
        return $this->setParameter('dt', $value);
    }

    /**
     * @return mixed
     */
    public function getDt()
    {
        return $this->getParameter('dt');
    }

    /**
     * @param mixed $value
     * @return $this
     */
    public function setRpt($value)
    {
        return $this->setParameter('rpt', $value);
    }

    /**
     * @return mixed
     */
    public function getRpt()
    {
        return $this->getParameter('rpt');
    }

    /**
     * @return array<string, mixed>
     */
    public function getData()
    {
        $this->validate('bid', 'key1', 'key2');
        return [];
    }

    /**
     * @param mixed $data
     * @return PurchaseResponse
     */
    public function sendData($data)
    {
        $data = is_array($data) ? $data : [];
        $params = [];

        $params['QP'] = '1.0';
        $params['BID'] = $this->getBid();
        $params['AM'] = $this->getAmount();
        if ((bool) $this->getCurrency()) {
            $params['CC'] = $this->getCurrency();
        }
        if ((bool) $this->getVs()) {
            $params['VS'] = $this->getVs();
        }
        if ((bool) $this->getCs()) {
            $params['CS'] = $this->getCs();
        }
        if ((bool) $this->getSs()) {
            $params['SS'] = $this->getSs();
        }
        if ((bool) $this->getRpt()) {
            $params['RPT'] = intval(Str::from($this->getRpt()));
        }
        if ((bool) $this->getMsg()) {
            $params['MSG'] = $this->getMsg();
        }

        $requestString = $this->prepareRequestString($params);

        $data['vs'] = $this->getVs();
        $data['template'] = 300;
        $data['qr_url'] = $this->getQrEndpoint() . '?text=' . $requestString . '&template=' . $data['template'];
        $qrCode = new QrCode(data: $requestString, size: 300);

        $writer = new PngWriter();
        $data['qr_data'] = base64_encode($writer->write($qrCode)->getString());

        if ((bool) $this->getRurl()) {
            $params['RURL'] = str_replace('*', '%2A', Str::from($this->getRurl()));
        }

        $appRequestString = $this->prepareRequestString($params);

        $app = 'viamo';
        if ($this->getTestMode()) {
            $app = 'viamo-staging';
        }
        $data['app_link'] = $app . '://?requestString=' . $appRequestString;

        return $this->response = new PurchaseResponse($this, $data);
    }

    /**
     * @param array<string, mixed> $params
     */
    private function prepareRequestString(array $params): string
    {
        $paramsStrings = [];
        foreach ($params as $key => $value) {
            $paramsStrings[] = $key . ':' . Str::from($value);
        }

        $requestString = implode('*', $paramsStrings);

        $sign = new ViamoSign();

        $requestString .= '*SIG:' . $sign->sign($requestString, $this->getKey1());

        return $requestString;
    }

    /**
     * @return string
     */
    public function getQrEndpoint()
    {
        return 'https://qr.viamo.sk/api/';
    }
}
