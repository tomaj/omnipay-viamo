<?php

declare(strict_types=1);

namespace Omnipay\Viamo\Message;

use Omnipay\Common\Message\AbstractResponse;
use Omnipay\Viamo\Core\Str;

/**
 * The verified webhook notification.
 */
final class WebhookResponse extends AbstractResponse
{
    /**
     * True only for a correctly signed notification with result `OK`.
     */
    #[\Override]
    public function isSuccessful(): bool
    {
        return is_array($this->data) && ($this->data['success'] ?? null) === true;
    }

    #[\Override]
    public function isRedirect(): bool
    {
        return false;
    }

    /**
     * Why the notification was rejected (`Wrong signature`, `Invalid result`, …), or null.
     */
    public function errorMessage(): ?string
    {
        return $this->getValue('error');
    }

    /**
     * Same as errorMessage().
     */
    #[\Override]
    public function getMessage(): ?string
    {
        return $this->errorMessage();
    }

    public function getVs(): ?string
    {
        return $this->getValue('vs');
    }

    /**
     * The paid amount as Viamo sent it (e.g. `24.90`). It is covered by the signature and set only when the
     * signature is valid (null for `Wrong signature`). Compare it with your order total before marking it paid.
     */
    public function getAmount(): ?string
    {
        return $this->getValue('amount');
    }

    public function getPaymentId(): ?string
    {
        return $this->getValue('payment_id');
    }

    public function getNotificationId(): ?string
    {
        return $this->getValue('notification_id');
    }

    private function getValue(string $key): ?string
    {
        return is_array($this->data) ? Str::nullable($this->data[$key] ?? null) : null;
    }
}
