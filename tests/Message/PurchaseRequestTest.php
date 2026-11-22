<?php

declare(strict_types=1);

namespace Omnipay\Viamo\Tests\Message;

use Omnipay\Common\Exception\InvalidRequestException;
use Omnipay\Viamo\Exception\InvalidKeyException;
use Omnipay\Viamo\Message\PurchaseRequest;
use Omnipay\Viamo\Message\PurchaseResponse;
use Omnipay\Viamo\Tests\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;

final class PurchaseRequestTest extends TestCase
{
    public function testFullRequest(): void
    {
        $response = $this->gateway()->purchase([
            'amount' => '10.00',
            'currency' => 'EUR',
            'vs' => '777',
            'msg' => 'Order 777',
            'rurl' => 'https://shop.example/return?a=*b',
        ])->send();

        self::assertTrue($response->isSuccessful());
        self::assertFalse($response->isRedirect());

        // SIG values from the openssl CLI
        $qrText = 'QP:1.0*BID:42*AM:10.00*CC:EUR*VS:777*MSG:Order 777*SIG:4EBE348B1F40E5598DBF0C109174E6A0';
        $appText = 'QP:1.0*BID:42*AM:10.00*CC:EUR*VS:777*MSG:Order 777*RURL:https://shop.example/return?a=%2Ab*SIG:D8076C95CC60208FF90BEAF9F7BACFA7';

        $data = $response->getData();
        self::assertIsArray($data);
        self::assertSame(['vs', 'template', 'qr_url', 'qr_data', 'app_link'], array_keys($data));
        self::assertSame('777', $data['vs']);
        self::assertSame(300, $data['template']);
        self::assertSame('https://qr.viamo.sk/api/?text=' . $qrText . '&template=300', $data['qr_url']);
        self::assertSame('viamo://?requestString=' . $appText, $data['app_link']);

        self::assertSame('777', $response->getVs());
        self::assertSame($data['qr_url'], $response->getQrUrl());
        self::assertSame($data['app_link'], $response->getAppLink());
        self::assertSame($data['qr_data'], $response->getQrData());
    }

    public function testQrCodeIsAPngOfTheRequestString(): void
    {
        $response = $this->gateway()->purchase(['amount' => '10.00', 'vs' => '777'])->send();

        $png = base64_decode((string) $response->getQrData(), true);
        self::assertIsString($png);
        self::assertStringStartsWith("\x89PNG\r\n\x1a\n", $png);
        $size = getimagesizefromstring($png);
        self::assertIsArray($size);
        self::assertSame(IMAGETYPE_PNG, $size[2]);
        self::assertSame($size[0], $size[1]);
        self::assertGreaterThanOrEqual(300, $size[0]);
    }

    public function testOptionalFieldsAndTestMode(): void
    {
        $gateway = $this->gateway();
        $gateway->setTestMode(true);

        $response = $gateway->purchase([
            'amount' => '1.50',
            'vs' => '0',
            'cs' => '0308',
            'ss' => '123',
            'rpt' => 3,
        ])->send();

        // "0" is treated as empty, like in 4.x
        $text = 'QP:1.0*BID:42*AM:1.50*CS:0308*SS:123*RPT:3*SIG:5FAE94365DE04B0E0E0D6A53FDAC9C78';
        self::assertSame('https://qr.viamo.sk/api/?text=' . $text . '&template=300', $response->getQrUrl());
        self::assertSame('viamo-staging://?requestString=' . $text, $response->getAppLink());
        self::assertSame('0', $response->getVs());
    }

    public function testMinimalRequest(): void
    {
        $response = $this->gateway()->purchase(['amount' => '99.99'])->send();

        $data = $response->getData();
        self::assertIsArray($data);
        self::assertNull($data['vs']);
        self::assertNull($response->getVs());
        self::assertStringStartsWith('https://qr.viamo.sk/api/?text=QP:1.0*BID:42*AM:99.99*SIG:', (string) $response->getQrUrl());
        self::assertStringStartsWith('viamo://?requestString=QP:1.0*BID:42*AM:99.99*SIG:', (string) $response->getAppLink());
    }

    public function testEmptyOptionalFieldsAreLeftOut(): void
    {
        $response = $this->gateway()->purchase([
            'amount' => '99.99',
            'vs' => '',
            'cs' => '0',
            'ss' => '',
            'rpt' => '0',
            'msg' => '0',
            'rurl' => '0',
        ])->send();

        self::assertStringStartsWith('https://qr.viamo.sk/api/?text=QP:1.0*BID:42*AM:99.99*SIG:', (string) $response->getQrUrl());
        self::assertStringStartsWith('viamo://?requestString=QP:1.0*BID:42*AM:99.99*SIG:', (string) $response->getAppLink());
    }

    public function testRptIsSentAsAnInteger(): void
    {
        $response = $this->gateway()->purchase(['amount' => '99.99', 'currency' => 'EUR', 'rpt' => '2x'])->send();

        self::assertSame(
            'https://qr.viamo.sk/api/?text=QP:1.0*BID:42*AM:99.99*CC:EUR*RPT:2*SIG:BD772643C781F480503DC04FF1469702&template=300',
            $response->getQrUrl(),
        );
    }

    public function testEveryAsteriskInTheReturnUrlIsEscaped(): void
    {
        $response = $this->gateway()->purchase(['amount' => '1.00', 'rurl' => 'https://a.example/*/x*y'])->send();

        self::assertStringContainsString('*RURL:https://a.example/%2A/x%2Ay*SIG:', (string) $response->getAppLink());
        self::assertStringNotContainsString('RURL', (string) $response->getQrUrl());
    }

    /**
     * @return iterable<string, array{\Closure(PurchaseRequest, ?string): PurchaseRequest, \Closure(PurchaseRequest): ?string}>
     */
    public static function parameters(): iterable
    {
        yield 'rurl' => [static fn(PurchaseRequest $r, ?string $v): PurchaseRequest => $r->setRurl($v), static fn(PurchaseRequest $r): ?string => $r->getRurl()];
        yield 'vs' => [static fn(PurchaseRequest $r, ?string $v): PurchaseRequest => $r->setVs($v), static fn(PurchaseRequest $r): ?string => $r->getVs()];
        yield 'cs' => [static fn(PurchaseRequest $r, ?string $v): PurchaseRequest => $r->setCs($v), static fn(PurchaseRequest $r): ?string => $r->getCs()];
        yield 'ss' => [static fn(PurchaseRequest $r, ?string $v): PurchaseRequest => $r->setSs($v), static fn(PurchaseRequest $r): ?string => $r->getSs()];
        yield 'msg' => [static fn(PurchaseRequest $r, ?string $v): PurchaseRequest => $r->setMsg($v), static fn(PurchaseRequest $r): ?string => $r->getMsg()];
        yield 'dt' => [static fn(PurchaseRequest $r, ?string $v): PurchaseRequest => $r->setDt($v), static fn(PurchaseRequest $r): ?string => $r->getDt()];
        yield 'rpt' => [static fn(PurchaseRequest $r, ?string $v): PurchaseRequest => $r->setRpt($v), static fn(PurchaseRequest $r): ?string => $r->getRpt()];
    }

    /**
     * @param \Closure(PurchaseRequest, ?string): PurchaseRequest $set
     * @param \Closure(PurchaseRequest): ?string $get
     */
    #[DataProvider('parameters')]
    public function testParameter(\Closure $set, \Closure $get): void
    {
        $request = $this->gateway()->purchase();

        self::assertNull($get($request));
        self::assertSame($request, $set($request, 'value'));
        self::assertSame('value', $get($request));
        $set($request, null);
        self::assertNull($get($request));
    }

    public function testRptAcceptsAnInteger(): void
    {
        $request = $this->gateway()->purchase();
        $request->setRpt(5);

        self::assertSame('5', $request->getRpt());
    }

    public function testQrEndpoint(): void
    {
        self::assertSame('https://qr.viamo.sk/api/', $this->gateway()->purchase()->getQrEndpoint());
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function requiredParameters(): iterable
    {
        yield 'bid' => ['bid'];
        yield 'key1' => ['key1'];
        yield 'key2' => ['key2'];
    }

    #[DataProvider('requiredParameters')]
    public function testRequiredParameters(string $parameter): void
    {
        self::assertThrows(
            InvalidRequestException::class,
            "The {$parameter} parameter is required",
            fn(): PurchaseResponse => $this->gateway()->purchase(['amount' => '1.00', $parameter => null])->send(),
        );
    }

    public function testAnInvalidKeyThrows(): void
    {
        $this->expectException(InvalidKeyException::class);

        $this->gateway()->purchase(['amount' => '1.00', 'key1' => 'secret'])->send();
    }
}
