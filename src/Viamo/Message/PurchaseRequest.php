<?php

declare(strict_types=1);

namespace Omnipay\Viamo\Message;

use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;
use Omnipay\Common\Exception\InvalidRequestException;
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
     * Separates the `KEY:value` fields of the request string, so no value may contain it.
     */
    private const string FIELD_SEPARATOR = '*';

    /**
     * Payment symbols are integers of at most 10 digits, like `X-VS`, `X-SS` and `X-KS` in the Short Payment
     * Descriptor format whose `KEY:value*KEY:value` layout the Viamo request string uses.
     */
    private const string SYMBOL_PATTERN = '/\A[0-9]{1,10}\z/';

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
     * Variable symbol: identifies the order in the payment result. Up to 10 digits.
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
     * Constant symbol. Up to 10 digits.
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
     * Specific symbol. Up to 10 digits.
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
     * Message for the payer. Must not contain `*`, the field separator of the request string.
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
        return $this->setParameter('rpt', $value);
    }

    /**
     * Validates the parameters; the request string itself is built by sendData().
     *
     * @return array{}
     * @throws InvalidRequestException when `bid`, `key1` or `amount` is missing or empty, a payment symbol
     *                                 is not up to 10 digits, or a field value contains `*`
     */
    #[\Override]
    public function getData(): array
    {
        foreach (['bid', 'key1', 'amount'] as $key) {
            if (in_array($this->getParameter($key), [null, ''], true)) {
                throw new InvalidRequestException("The {$key} parameter is required");
            }
        }

        foreach (['vs' => $this->getVs(), 'cs' => $this->getCs(), 'ss' => $this->getSs()] as $key => $value) {
            if ($this->isFilled($value) && preg_match(self::SYMBOL_PATTERN, $value) !== 1) {
                throw new InvalidRequestException("The {$key} parameter must be a number of at most 10 digits");
            }
        }

        return [];
    }

    #[\Override]
    public function send(): PurchaseResponse
    {
        return $this->sendData($this->getData());
    }

    #[\Override]
    public function sendData(mixed $data): PurchaseResponse
    {
        $params = [
            'QP' => '1.0',
            'BID' => $this->getBid(),
            'AM' => $this->getAmount(),
        ];
        $optional = [
            'CC' => $this->getCurrency(),
            'VS' => $this->getVs(),
            'CS' => $this->getCs(),
            'SS' => $this->getSs(),
            'RPT' => $this->getRpt(),
            'MSG' => $this->getMsg(),
        ];
        foreach ($optional as $key => $value) {
            if ($this->isFilled($value)) {
                $params[$key] = $key === 'RPT' ? (string) (int) $value : $value;
            }
        }

        $requestString = $this->prepareRequestString($params);

        $qrCode = new QrCode(data: $requestString, size: self::QR_TEMPLATE);

        $responseData = [
            'vs' => $this->getVs(),
            'template' => self::QR_TEMPLATE,
            'qr_url' => $this->getQrEndpoint() . '?' . http_build_query(['text' => $requestString, 'template' => self::QR_TEMPLATE], '', '&', PHP_QUERY_RFC3986),
            'qr_data' => base64_encode(new PngWriter()->write($qrCode)->getString()),
        ];

        $rurl = $this->getRurl();
        if ($this->isFilled($rurl)) {
            $params['RURL'] = str_replace(self::FIELD_SEPARATOR, '%2A', $rurl);
        }

        $app = $this->getTestMode() ? 'viamo-staging' : 'viamo';
        $responseData['app_link'] = $app . '://?requestString=' . rawurlencode($this->prepareRequestString($params));

        return $this->response = new PurchaseResponse($this, $responseData);
    }

    public function getQrEndpoint(): string
    {
        return self::QR_ENDPOINT;
    }

    /**
     * Optional fields are sent only when they are not empty; "0" counts as empty, like PHP's truthiness.
     *
     * @phpstan-assert-if-true non-empty-string $value
     */
    private function isFilled(?string $value): bool
    {
        return !in_array($value, [null, '', '0'], true);
    }

    /**
     * @param array<string, ?string> $params
     * @throws InvalidRequestException when a value contains the field separator `*`
     */
    private function prepareRequestString(array $params): string
    {
        $paramsStrings = [];
        foreach ($params as $key => $value) {
            if (str_contains((string) $value, self::FIELD_SEPARATOR)) {
                throw new InvalidRequestException('The ' . strtolower($key) . ' parameter must not contain "*", the field separator of the Viamo request string');
            }
            $paramsStrings[] = $key . ':' . $value;
        }

        $requestString = implode(self::FIELD_SEPARATOR, $paramsStrings);

        return $requestString . '*SIG:' . new ViamoSign()->sign($requestString, (string) $this->getKey1());
    }
}
