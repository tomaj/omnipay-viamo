<?php

declare(strict_types=1);

namespace Omnipay\Viamo\Tests\Message;

use Omnipay\Common\Exception\InvalidRequestException;
use Omnipay\Viamo\Exception\InvalidKeyException;
use Omnipay\Viamo\Gateway;
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
        self::assertSame('https://qr.viamo.sk/api/?text=' . rawurlencode($qrText) . '&template=300', $data['qr_url']);
        self::assertSame('viamo://?requestString=' . rawurlencode($appText), $data['app_link']);
        self::assertSame($qrText, $this->qrText($response));
        self::assertSame($appText, $this->appRequestString($response));

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
        self::assertSame('https://qr.viamo.sk/api/?text=' . rawurlencode($text) . '&template=300', $response->getQrUrl());
        self::assertSame('viamo-staging://?requestString=' . rawurlencode($text), $response->getAppLink());
        self::assertSame('0', $response->getVs());
    }

    public function testMinimalRequest(): void
    {
        $response = $this->gateway()->purchase(['amount' => '99.99'])->send();

        $data = $response->getData();
        self::assertIsArray($data);
        self::assertNull($data['vs']);
        self::assertNull($response->getVs());
        self::assertStringStartsWith('QP:1.0*BID:42*AM:99.99*SIG:', $this->qrText($response));
        self::assertStringStartsWith('QP:1.0*BID:42*AM:99.99*SIG:', $this->appRequestString($response));
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

        self::assertStringStartsWith('QP:1.0*BID:42*AM:99.99*SIG:', $this->qrText($response));
        self::assertStringStartsWith('QP:1.0*BID:42*AM:99.99*SIG:', $this->appRequestString($response));
    }

    public function testRptIsSentAsAnInteger(): void
    {
        $response = $this->gateway()->purchase(['amount' => '99.99', 'currency' => 'EUR', 'rpt' => '2x'])->send();

        self::assertSame('QP:1.0*BID:42*AM:99.99*CC:EUR*RPT:2*SIG:BD772643C781F480503DC04FF1469702', $this->qrText($response));
    }

    public function testEveryAsteriskInTheReturnUrlIsEscaped(): void
    {
        $response = $this->gateway()->purchase(['amount' => '1.00', 'rurl' => 'https://a.example/*/x*y'])->send();

        self::assertStringContainsString('*RURL:https://a.example/%2A/x%2Ay*SIG:', $this->appRequestString($response));
        self::assertStringNotContainsString('RURL', (string) $response->getQrUrl());
    }

    public function testUrlsAreEncodedAndDecodeToTheSignedRequestString(): void
    {
        $response = $this->gateway()->purchase([
            'amount' => '24.90',
            'currency' => 'EUR',
            'vs' => '2026000123',
            'msg' => 'Fish & chips #7 50% off',
            'rurl' => 'https://shop.example/return?order=1&lang=sk#top',
        ])->send();

        // SIG values from the openssl CLI; the signed strings are not encoded, only the URLs that carry them
        $qrText = 'QP:1.0*BID:42*AM:24.90*CC:EUR*VS:2026000123*MSG:Fish & chips #7 50% off*SIG:5B66808B063BAC533CFD1D81CDD34046';
        $appText = 'QP:1.0*BID:42*AM:24.90*CC:EUR*VS:2026000123*MSG:Fish & chips #7 50% off*RURL:https://shop.example/return?order=1&lang=sk#top*SIG:81F453DFFD727DC9E9A18EBD7CCF9C99';

        self::assertSame($qrText, $this->qrText($response));
        self::assertSame($appText, $this->appRequestString($response));
        self::assertSame(
            'https://qr.viamo.sk/api/?text=QP%3A1.0%2ABID%3A42%2AAM%3A24.90%2ACC%3AEUR%2AVS%3A2026000123%2AMSG%3AFish%20%26%20chips%20%237%2050%25%20off%2ASIG%3A5B66808B063BAC533CFD1D81CDD34046&template=300',
            $response->getQrUrl(),
        );
        self::assertNotFalse(filter_var($response->getQrUrl(), FILTER_VALIDATE_URL));
        self::assertMatchesRegularExpression('/\Aviamo:\/\/\?requestString=[A-Za-z0-9%._~-]+\z/', (string) $response->getAppLink());
    }

    public function testLongestPaymentSymbolsAreAccepted(): void
    {
        $response = $this->gateway()->purchase(['amount' => '1.00', 'vs' => '1234567890', 'cs' => '1234567890', 'ss' => '1234567890'])->send();

        self::assertSame('QP:1.0*BID:42*AM:1.00*VS:1234567890*CS:1234567890*SS:1234567890*SIG:EBB09A4A56D849B0D66783E45674626E', $this->qrText($response));
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function invalidSymbols(): iterable
    {
        foreach (['vs', 'cs', 'ss'] as $symbol) {
            yield "{$symbol} with a field injected" => [$symbol, '1*AM:0.01'];
            yield "{$symbol} with 11 digits" => [$symbol, '12345678901'];
            yield "{$symbol} with a letter" => [$symbol, '12a'];
            yield "{$symbol} negative" => [$symbol, '-1'];
            yield "{$symbol} with a space" => [$symbol, ' 1'];
            yield "{$symbol} with a trailing newline" => [$symbol, "1\n"];
        }
    }

    #[DataProvider('invalidSymbols')]
    public function testPaymentSymbolsMustBeUpToTenDigits(string $parameter, string $value): void
    {
        self::assertThrows(
            InvalidRequestException::class,
            "The {$parameter} parameter must be a number of at most 10 digits",
            fn(): PurchaseResponse => $this->gateway()->purchase(['amount' => '1.00', $parameter => $value])->send(),
        );
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function fieldInjections(): iterable
    {
        yield 'msg with a second amount' => ['msg', 'Order 1*AM:0.01'];
        yield 'msg with a field separator only' => ['msg', 'a*b'];
        yield 'bid' => ['bid', '42*AM:0.01'];
    }

    #[DataProvider('fieldInjections')]
    public function testAFieldSeparatorInAValueIsRejected(string $parameter, string $value): void
    {
        self::assertThrows(
            InvalidRequestException::class,
            "The {$parameter} parameter must not contain \"*\", the field separator of the Viamo request string",
            fn(): PurchaseResponse => $this->gateway()->purchase(['amount' => '1.00', $parameter => $value])->send(),
        );
    }

    /**
     * Decodes the `text` query parameter of the QR API URL.
     */
    private function qrText(PurchaseResponse $response): string
    {
        parse_str((string) parse_url((string) $response->getQrUrl(), PHP_URL_QUERY), $query);
        self::assertSame(['text', 'template'], array_keys($query));
        self::assertSame('300', $query['template']);
        self::assertIsString($query['text']);

        return $query['text'];
    }

    /**
     * Decodes the `requestString` query parameter of the app link.
     */
    private function appRequestString(PurchaseResponse $response): string
    {
        [$scheme, $queryString] = explode('?', (string) $response->getAppLink(), 2) + ['', ''];
        self::assertContains($scheme, ['viamo://', 'viamo-staging://']);
        parse_str($queryString, $query);
        self::assertSame(['requestString'], array_keys($query));
        self::assertIsString($query['requestString']);

        return $query['requestString'];
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
     * @return iterable<string, array{string, ?string}>
     */
    public static function requiredParameters(): iterable
    {
        foreach (['bid', 'key1', 'amount'] as $parameter) {
            yield "{$parameter} null" => [$parameter, null];
            yield "{$parameter} empty" => [$parameter, ''];
        }
    }

    #[DataProvider('requiredParameters')]
    public function testRequiredParameters(string $parameter, ?string $value): void
    {
        self::assertThrows(
            InvalidRequestException::class,
            "The {$parameter} parameter is required",
            fn(): PurchaseResponse => $this->gateway()->purchase(['amount' => '1.00', $parameter => $value])->send(),
        );
    }

    public function testAmountIsRequired(): void
    {
        self::assertThrows(InvalidRequestException::class, 'The amount parameter is required', fn(): PurchaseResponse => $this->gateway()->purchase()->send());
    }

    public function testThroughTheGatewayDefaultsAnEmptyBidIsRejected(): void
    {
        $gateway = new Gateway();
        $gateway->initialize(['key1' => self::KEY1]);

        self::assertThrows(InvalidRequestException::class, 'The bid parameter is required', static fn(): PurchaseResponse => $gateway->purchase(['amount' => '1.00'])->send());
    }

    public function testKey2IsNotRequired(): void
    {
        self::assertTrue($this->gateway()->purchase(['amount' => '1.00', 'key2' => null])->send()->isSuccessful());
    }

    public function testAnInvalidKeyThrows(): void
    {
        $this->expectException(InvalidKeyException::class);

        $this->gateway()->purchase(['amount' => '1.00', 'key1' => 'secret'])->send();
    }
}
