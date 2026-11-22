<?php

declare(strict_types=1);

namespace Omnipay\Viamo\Message;

use Omnipay\Common\Message\AbstractRequest;
use Omnipay\Viamo\Core\CredentialsParameters;
use Omnipay\Viamo\Core\Str;
use Omnipay\Viamo\Core\ViamoSign;

/**
 * Verifies the `responseString` query parameter that Viamo appends to the return URL.
 */
final class CompletePurchaseRequest extends AbstractRequest
{
    use CredentialsParameters;

    /**
     * @return array<string, string>
     */
    #[\Override]
    public function getData(): array
    {
        $responseString = Str::from($this->httpRequest->query->all()['responseString'] ?? null);
        $pos = strpos($responseString, '*SIG:');
        $input = substr($responseString, 0, (int) $pos);

        $parts = explode('*', $responseString);
        $inputData = [];
        foreach ($parts as $pairs) {
            [$key, $value] = explode(':', $pairs);
            $inputData[$key] = $value;
        }

        if ((string) $this->getBid() !== $inputData['BID']) {
            return [
                'RES' => 'ERROR',
                'VS' => $inputData['VS'],
                'message' => 'Wrong BID',
            ];
        }

        if (!new ViamoSign()->verify($input, (string) $this->getKey1(), $inputData['SIG'])) {
            return [
                'RES' => 'ERROR',
                'VS' => $inputData['VS'],
                'message' => 'Wrong sign',
            ];
        }

        return [
            'RES' => $inputData['RES'],
            'VS' => $inputData['VS'],
        ];
    }

    #[\Override]
    public function sendData(mixed $data): CompletePurchaseResponse
    {
        return $this->response = new CompletePurchaseResponse($this, $data);
    }
}
