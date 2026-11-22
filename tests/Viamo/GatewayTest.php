<?php

namespace Omnipay\Viamo;

use Omnipay\Common\Http\ClientInterface;
use Omnipay\Tests\GatewayTestCase;
use Symfony\Component\HttpFoundation\Request;

class GatewayTest extends GatewayTestCase
{
    public function setUp(): void
    {
        parent::setUp();

        $httpClient = $this->getHttpClient();
        $httpRequest = $this->getHttpRequest();
        if (!$httpClient instanceof ClientInterface || !$httpRequest instanceof Request) {
            throw new \LogicException('Unexpected HTTP client or request from GatewayTestCase.');
        }

        $this->gateway = new Gateway($httpClient, $httpRequest);
    }

    public function testPurchase(): void
    {
        self::markTestIncomplete('Not implemented yet.');
    }
}
