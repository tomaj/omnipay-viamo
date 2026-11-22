<?php

declare(strict_types=1);

namespace Omnipay\Viamo\Tests\Message;

use Omnipay\Viamo\Message\CompletePurchaseResponse;
use Omnipay\Viamo\Message\PurchaseResponse;
use Omnipay\Viamo\Message\WebhookResponse;
use Omnipay\Viamo\Tests\TestCase;

final class ResponsesTest extends TestCase
{
    public function testResponsesWithoutArrayData(): void
    {
        $request = $this->gateway()->purchase();

        $purchase = new PurchaseResponse($request, null);
        self::assertNull($purchase->getVs());
        self::assertNull($purchase->getQrData());
        self::assertNull($purchase->getQrUrl());
        self::assertNull($purchase->getAppLink());

        $complete = new CompletePurchaseResponse($request, 'OK');
        self::assertFalse($complete->isSuccessful());
        self::assertNull($complete->getRes());
        self::assertNull($complete->getVs());
        self::assertNull($complete->getMessage());

        $webhook = new WebhookResponse($request, true);
        self::assertFalse($webhook->isSuccessful());
        self::assertNull($webhook->errorMessage());
        self::assertNull($webhook->getVs());
    }

    public function testWebhookSuccessMustBeTrue(): void
    {
        $request = $this->gateway()->webhook();

        self::assertFalse(new WebhookResponse($request, ['success' => 1])->isSuccessful());
        self::assertFalse(new WebhookResponse($request, ['success' => 'true'])->isSuccessful());
        self::assertFalse(new WebhookResponse($request, [])->isSuccessful());
        self::assertTrue(new WebhookResponse($request, ['success' => true])->isSuccessful());
    }

    public function testCompletePurchaseIsSuccessfulOnlyForOk(): void
    {
        $request = $this->gateway()->completePurchase();

        self::assertTrue(new CompletePurchaseResponse($request, ['RES' => 'OK'])->isSuccessful());
        self::assertFalse(new CompletePurchaseResponse($request, ['RES' => 'ok'])->isSuccessful());
        self::assertFalse(new CompletePurchaseResponse($request, ['RES' => 'FAIL'])->isSuccessful());
    }
}
