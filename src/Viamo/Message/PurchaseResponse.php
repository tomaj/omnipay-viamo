<?php

declare(strict_types=1);

namespace Omnipay\Viamo\Message;

use Omnipay\Common\Message\AbstractResponse;
use Omnipay\Viamo\Core\Str;

/**
 * The payment request as a QR code and an app link. It is not a redirect: show the QR code
 * or open the app link, then wait for the return URL or the webhook.
 */
final class PurchaseResponse extends AbstractResponse
{
    #[\Override]
    public function isSuccessful(): bool
    {
        return true;
    }

    #[\Override]
    public function isRedirect(): bool
    {
        return false;
    }

    public function getVs(): ?string
    {
        return $this->getValue('vs');
    }

    /**
     * The QR code as a base64-encoded PNG image.
     */
    public function getQrData(): ?string
    {
        return $this->getValue('qr_data');
    }

    /**
     * URL of Viamo's QR API that renders the same QR code.
     */
    public function getQrUrl(): ?string
    {
        return $this->getValue('qr_url');
    }

    /**
     * `viamo://` (`viamo-staging://` in test mode) link that opens the payment in the Viamo app.
     */
    public function getAppLink(): ?string
    {
        return $this->getValue('app_link');
    }

    private function getValue(string $key): ?string
    {
        return is_array($this->data) ? Str::nullable($this->data[$key] ?? null) : null;
    }
}
