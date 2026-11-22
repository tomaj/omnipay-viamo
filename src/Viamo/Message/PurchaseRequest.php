<?php

declare(strict_types=1);

namespace Omnipay\Viamo\Message;

use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;
use Omnipay\Common\Message\AbstractRequest;
use Omnipay\Viamo\Core\CredentialsParameters;
use Omnipay\Viamo\Core\Str;
use Omnipay\Viamo\Core\ViamoSign;

/**
 * Builds the signed Viamo payment request (`QP:1.0*BID:…*AM:…*SIG:…`) and renders it as a QR code and an app link.
 */
final class PurchaseRequest extends AbstractRequest
{
    use CredentialsParameters;

    public const string QR_ENDPOINT = 'https://qr.viamo.sk/api/';

    public const int QR_TEMPLATE = 300;

    /**
     * The URL Viamo returns the customer to, with the signed `responseString` appended.
     */
    public function getRurl(): ?string
    {
        return Str::nullable($this->getParameter('rurl'));
    }

    public function setRurl(?string $value): static
    {
        return $this->setParameter('rurl', $value);
    }

    /**
     * Variable symbol: identifies the order in the payment result.
     */
    public function getVs(): ?string
    {
        return Str::nullable($this->getParameter('vs'));
    }

    public function setVs(?string $value): static
    {
        return $this->setParameter('vs', $value);
    }

    /**
     * Constant symbol.
     */
    public function getCs(): ?string
    {
        return Str::nullable($this->getParameter('cs'));
    }

    public function setCs(?string $value): static
    {
        return $this->setParameter('cs', $value);
    }

    /**
     * Specific symbol.
     */
    public function getSs(): ?string
    {
        return Str::nullable($this->getParameter('ss'));
    }

    public function setSs(?string $value): static
    {
        return $this->setParameter('ss', $value);
    }

    /**
     * Message for the payer.
     */
    public function getMsg(): ?string
    {
        return Str::nullable($this->getParameter('msg'));
    }

    public function setMsg(?string $value): static
    {
        return $this->setParameter('msg', $value);
    }

    /**
     * Stored for the caller; Viamo's request string has no field for it.
     */
    public function getDt(): ?string
    {
        return Str::nullable($this->getParameter('dt'));
    }

    public function setDt(?string $value): static
    {
        return $this->setParameter('dt', $value);
    }

    /**
     * `RPT` field of the request string, sent as an integer.
     */
    public function getRpt(): ?string
    {
        return Str::nullable($this->getParameter('rpt'));
    }

    public function setRpt(int|string|null $value): static
    {
        return $this->setParameter('rpt', $value === null ? null : (string) $value);
    }

    /**
     * @return array{}
     */
    #[\Override]
    public function getData(): array
    {
        $this->validate('bid', 'key1', 'key2');

        return [];
    }

    #[\Override]
    public function sendData(mixed $data): PurchaseResponse
    {
        $params = [
            'QP' => '1.0',
            'BID' => $this->getBid(),
            'AM' => $this->getAmount(),
        ];
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
            $params['RPT'] = (string) (int) $this->getRpt();
        }
        if ((bool) $this->getMsg()) {
            $params['MSG'] = $this->getMsg();
        }

        $requestString = $this->prepareRequestString($params);

        $qrCode = new QrCode(data: $requestString, size: self::QR_TEMPLATE);

        $responseData = [
            'vs' => $this->getVs(),
            'template' => self::QR_TEMPLATE,
            'qr_url' => $this->getQrEndpoint() . '?text=' . $requestString . '&template=' . self::QR_TEMPLATE,
            'qr_data' => base64_encode(new PngWriter()->write($qrCode)->getString()),
        ];

        if ((bool) $this->getRurl()) {
            $params['RURL'] = str_replace('*', '%2A', $this->getRurl());
        }

        $app = $this->getTestMode() ? 'viamo-staging' : 'viamo';
        $responseData['app_link'] = $app . '://?requestString=' . $this->prepareRequestString($params);

        return $this->response = new PurchaseResponse($this, $responseData);
    }

    public function getQrEndpoint(): string
    {
        return self::QR_ENDPOINT;
    }

    /**
     * @param array<string, ?string> $params
     */
    private function prepareRequestString(array $params): string
    {
        $paramsStrings = [];
        foreach ($params as $key => $value) {
            $paramsStrings[] = $key . ':' . $value;
        }

        $requestString = implode('*', $paramsStrings);

        return $requestString . '*SIG:' . new ViamoSign()->sign($requestString, (string) $this->getKey1());
    }
}
