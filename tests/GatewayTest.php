<?php

declare(strict_types=1);

namespace Omnipay\Viamo\Tests;

use Omnipay\Omnipay;
use Omnipay\Viamo\Gateway;
use Symfony\Component\HttpFoundation\Request;

final class GatewayTest extends TestCase
{
    public function testOmnipayCreatesTheGateway(): void
    {
        $gateway = Omnipay::create('Viamo');

        self::assertInstanceOf(Gateway::class, $gateway);
        self::assertSame('Viamo Gateway', $gateway->getName());
        self::assertSame('Viamo', $gateway->getShortName());
    }

    public function testDefaults(): void
    {
        $gateway = new Gateway(null, new Request());

        self::assertSame(['bid' => '', 'key1' => '', 'key2' => '', 'testMode' => false], $gateway->getDefaultParameters());

        $gateway->initialize();

        self::assertSame('', $gateway->getBid());
        self::assertSame('', $gateway->getKey1());
        self::assertSame('', $gateway->getKey2());
        self::assertFalse($gateway->getTestMode());
    }

    public function testCredentials(): void
    {
        $gateway = new Gateway(null, new Request());

        self::assertSame($gateway, $gateway->setBid('42'));
        self::assertSame($gateway, $gateway->setKey1(self::KEY1));
        self::assertSame($gateway, $gateway->setKey2(self::KEY2));
        self::assertSame('42', $gateway->getBid());
        self::assertSame(self::KEY1, $gateway->getKey1());
        self::assertSame(self::KEY2, $gateway->getKey2());
    }

    public function testInitializeConvertsAnIntegerBid(): void
    {
        $gateway = new Gateway(null, new Request());
        $gateway->initialize(['bid' => 42]);

        self::assertSame('42', $gateway->getBid());
    }

    public function testPurchasePassesTheGatewayParametersToTheRequest(): void
    {
        $gateway = $this->gateway();
        $gateway->setTestMode(true);

        $request = $gateway->purchase(['amount' => '10.00', 'vs' => '777']);

        self::assertSame(self::BID, $request->getBid());
        self::assertSame(self::KEY1, $request->getKey1());
        self::assertSame(self::KEY2, $request->getKey2());
        self::assertTrue($request->getTestMode());
        self::assertSame('10.00', $request->getAmount());
        self::assertSame('777', $request->getVs());
    }

    public function testRequestParametersOverrideTheGatewayParameters(): void
    {
        $request = $this->gateway()->purchase(['bid' => '43']);

        self::assertSame('43', $request->getBid());
        self::assertSame(self::KEY1, $request->getKey1());
    }

    public function testCompletePurchase(): void
    {
        $request = $this->gateway()->completePurchase(['bid' => '43']);

        self::assertSame('43', $request->getBid());
        self::assertSame(self::KEY1, $request->getKey1());
        self::assertSame(self::KEY2, $request->getKey2());
    }

    public function testWebhook(): void
    {
        $request = $this->gateway()->webhook(['json' => ['payment' => []]]);

        self::assertSame(self::BID, $request->getBid());
        self::assertSame(self::KEY2, $request->getKey2());
        self::assertSame(['payment' => []], $request->getJson());
    }
}
