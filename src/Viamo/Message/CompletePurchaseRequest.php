<?php

declare(strict_types=1);

namespace Omnipay\Viamo\Message;

use Omnipay\Common\Message\AbstractRequest;
use Omnipay\Viamo\Core\CredentialsParameters;
use Omnipay\Viamo\Core\ViamoSign;

/**
 * Verifies the `responseString` query parameter that Viamo appends to the return URL.
 */
final class CompletePurchaseRequest extends AbstractRequest
{
    use CredentialsParameters;

    private const string SIGNATURE_SEPARATOR = '*SIG:';

    /**
     * The verified result: `RES` (`OK`, `FAIL`, or `ERROR` when the response cannot be trusted),
     * `VS` when present, and `message` with the reason for `ERROR`.
     *
     * @return array{RES: string, VS?: string, message?: string}
     */
    #[\Override]
    public function getData(): array
    {
        $responseString = $this->httpRequest->query->all()['responseString'] ?? null;
        if (!is_string($responseString) || $responseString === '') {
            return ['RES' => 'ERROR', 'message' => 'Missing responseString'];
        }

        $fields = $this->parseFields($responseString);

        $signaturePosition = strrpos($responseString, self::SIGNATURE_SEPARATOR);
        if ($signaturePosition === false) {
            return $this->error($fields, 'Missing signature');
        }
        $signedPart = substr($responseString, 0, $signaturePosition);
        $signature = substr($responseString, $signaturePosition + strlen(self::SIGNATURE_SEPARATOR));

        if (!isset($fields['BID']) || $fields['BID'] !== $this->getBid()) {
            return $this->error($fields, 'Wrong BID');
        }

        if (!new ViamoSign()->verify($signedPart, (string) $this->getKey1(), $signature)) {
            return $this->error($fields, 'Wrong sign');
        }

        if (!isset($fields['RES'])) {
            return $this->error($fields, 'Missing RES');
        }
        if (!isset($fields['VS'])) {
            return $this->error($fields, 'Missing VS');
        }

        return ['RES' => $fields['RES'], 'VS' => $fields['VS']];
    }

    /**
     * Parses `KEY:value*KEY:value*…`. A value may contain `:`; parts without `:` are ignored.
     *
     * @return array<string, string>
     */
    private function parseFields(string $responseString): array
    {
        $fields = [];
        foreach (explode('*', $responseString) as $pair) {
            $keyValue = explode(':', $pair, 2);
            if (count($keyValue) === 2) {
                $fields[$keyValue[0]] = $keyValue[1];
            }
        }

        return $fields;
    }

    /**
     * @param array<string, string> $fields
     * @return array{RES: string, VS?: string, message: string}
     */
    private function error(array $fields, string $message): array
    {
        $data = ['RES' => 'ERROR'];
        if (isset($fields['VS'])) {
            $data['VS'] = $fields['VS'];
        }
        $data['message'] = $message;

        return $data;
    }

    #[\Override]
    public function send(): CompletePurchaseResponse
    {
        return $this->sendData($this->getData());
    }

    #[\Override]
    public function sendData(mixed $data): CompletePurchaseResponse
    {
        return $this->response = new CompletePurchaseResponse($this, $data);
    }
}
