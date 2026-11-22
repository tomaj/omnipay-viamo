<?php

declare(strict_types=1);

namespace Omnipay\Viamo\Message;

use Omnipay\Common\Message\AbstractRequest;
use Omnipay\Viamo\Core\CredentialsParameters;
use Omnipay\Viamo\Core\Str;
use Omnipay\Viamo\Core\ViamoSign;

/**
 * Verifies a Viamo webhook notification (the decoded JSON body, passed as the `json` parameter).
 */
final class WebhookRequest extends AbstractRequest
{
    use CredentialsParameters;

    /**
     * @param array<mixed>|null $json
     */
    public function setJson(?array $json): static
    {
        return $this->setParameter('json', $json);
    }

    /**
     * @return array<mixed>|null
     */
    public function getJson(): ?array
    {
        $json = $this->getParameter('json');

        return is_array($json) ? $json : null;
    }

    /**
     * @return array{}
     */
    #[\Override]
    public function getData(): array
    {
        $this->validate('bid', 'key2', 'json');

        return [];
    }

    #[\Override]
    public function sendData(mixed $data): WebhookResponse
    {
        $json = $this->getJson();

        if ($json === null || !isset($json['payment'])) {
            return $this->respond(['success' => false, 'error' => 'Missing payment data']);
        }
        $payment = $json['payment'];
        if (!is_array($payment) || !isset($payment['result']) || !isset($payment['amount']) || !isset($payment['id'])) {
            return $this->respond(['success' => false, 'error' => 'Missing payment parameter']);
        }
        if (!isset($json['signature']) || !is_array($json['signature']) || !isset($json['signature']['sign'])) {
            return $this->respond(['success' => false, 'error' => 'Missing signature']);
        }

        $notificationId = $json['notificationId'] ?? null;

        $textToSign = '';
        foreach (['rid', 'vs', 'e2e'] as $optional) {
            if (isset($payment[$optional]) && (bool) $payment[$optional]) {
                $textToSign .= Str::from($payment[$optional]);
            }
        }
        $textToSign .= Str::from($payment['result']);
        $textToSign .= Str::from($payment['amount']);
        $textToSign .= Str::from($payment['id']);

        $result = ['vs' => $payment['vs'] ?? null, 'payment_id' => $payment['id'], 'notification_id' => $notificationId];

        if (!new ViamoSign()->verify($textToSign, (string) $this->getKey2(), $json['signature']['sign'])) {
            return $this->respond(['success' => false, 'error' => 'Wrong signature'] + $result);
        }
        if ($payment['result'] !== 'OK') {
            return $this->respond(['success' => false, 'error' => 'Invalid result'] + $result);
        }

        return $this->respond(['success' => true] + $result);
    }

    /**
     * @param array<string, mixed> $data
     */
    private function respond(array $data): WebhookResponse
    {
        return $this->response = new WebhookResponse($this, $data);
    }
}
