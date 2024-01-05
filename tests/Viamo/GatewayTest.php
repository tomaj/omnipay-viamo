<?php

namespace Omnipay\Viamo;

use Omnipay\Tests\GatewayTestCase;

class GatewayTest extends GatewayTestCase
{
	/**
     * @var Gateway
     */
    protected $gateway;

    public function setUp(): void
    {
        parent::setUp();
        $this->gateway = new Gateway($this->getHttpClient(), $this->getHttpRequest());
    }

    public function testPurchase()
    {
        
    }
}
