<?php

declare(strict_types=1);

namespace Omnipay\Viamo\Tests\Message;

use Omnipay\Viamo\Exception\InvalidKeyException;
use Omnipay\Viamo\Message\CompletePurchaseResponse;
use Omnipay\Viamo\Tests\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;

final class CompletePurchaseRequestTest extends TestCase
{
    // openssl CLI signatures with KEY1
    private const string SIG_OK = '04B15D742A42AD489F05F2174C32DBBD';  // BID:42*VS:777*RES:OK

    private const string SIG_FAIL = 'ECF29065A4E81458F3294C0D4567A4B6'; // BID:42*VS:777*RES:FAIL

    private function complete(mixed $responseString): CompletePurchaseResponse
    {
        return $this->gateway(['responseString' => $responseString])->completePurchase()->send();
    }

    public function testSuccessfulPayment(): void
    {
        $response = $this->complete('BID:42*VS:777*RES:OK*SIG:' . self::SIG_OK);

        self::assertTrue($response->isSuccessful());
        self::assertFalse($response->isRedirect());
        self::assertSame('OK', $response->getRes());
        self::assertSame('777', $response->getVs());
        self::assertNull($response->getMessage());
        self::assertSame(['RES' => 'OK', 'VS' => '777'], $response->getData());
    }

    public function testLowercaseSignatureIsAccepted(): void
    {
        $response = $this->complete('BID:42*VS:777*RES:OK*SIG:' . strtolower(self::SIG_OK));

        self::assertTrue($response->isSuccessful());
    }

    public function testFailedPayment(): void
    {
        $response = $this->complete('BID:42*VS:777*RES:FAIL*SIG:' . self::SIG_FAIL);

        self::assertFalse($response->isSuccessful());
        self::assertSame(['RES' => 'FAIL', 'VS' => '777'], $response->getData());
        self::assertSame('FAIL', $response->getRes());
        self::assertSame('777', $response->getVs());
    }

    public function testValueWithAColon(): void
    {
        $response = $this->complete('BID:42*VS:7:7*RES:OK*SIG:3809EAC60744BA25F3FEF1D51F29CFF6');

        self::assertTrue($response->isSuccessful());
        self::assertSame('7:7', $response->getVs());
    }

    public function testSignatureIsSplitAtTheLastSigField(): void
    {
        $response = $this->complete('BID:42*VS:777*RES:OK*SIG:X*NOTE:a*SIG:E061C9DCB2767C4985258F1650D10A44');

        self::assertTrue($response->isSuccessful());
        self::assertSame('777', $response->getVs());
    }

    public function testIntegerBidFromTheConfiguration(): void
    {
        $gateway = $this->gateway(['responseString' => 'BID:42*VS:777*RES:OK*SIG:' . self::SIG_OK]);
        $gateway->initialize(['bid' => 42, 'key1' => self::KEY1]);

        self::assertTrue($gateway->completePurchase()->send()->isSuccessful());
    }

    /**
     * @return iterable<string, array{mixed, array<string, string>}>
     */
    public static function rejected(): iterable
    {
        yield 'other merchant' => ['BID:43*VS:777*RES:OK*SIG:' . self::SIG_OK, ['RES' => 'ERROR', 'VS' => '777', 'message' => 'Wrong BID']];
        yield 'numeric-looking BID' => ['BID:042*VS:777*RES:OK*SIG:' . self::SIG_OK, ['RES' => 'ERROR', 'VS' => '777', 'message' => 'Wrong BID']];
        yield 'missing BID' => ['VS:777*RES:OK*SIG:' . self::SIG_OK, ['RES' => 'ERROR', 'VS' => '777', 'message' => 'Wrong BID']];
        yield 'signature of the failed result' => ['BID:42*VS:777*RES:OK*SIG:' . self::SIG_FAIL, ['RES' => 'ERROR', 'VS' => '777', 'message' => 'Wrong sign']];
        yield 'tampered VS' => ['BID:42*VS:778*RES:OK*SIG:' . self::SIG_OK, ['RES' => 'ERROR', 'VS' => '778', 'message' => 'Wrong sign']];
        yield 'signature of another key' => ['BID:42*VS:777*RES:OK*SIG:FAC0E8E3EB3BB2FFDFBE7FD016D25DA1', ['RES' => 'ERROR', 'VS' => '777', 'message' => 'Wrong sign']];
        yield 'truncated signature' => ['BID:42*VS:777*RES:OK*SIG:' . substr(self::SIG_OK, 0, 31), ['RES' => 'ERROR', 'VS' => '777', 'message' => 'Wrong sign']];
        yield 'empty signature' => ['BID:42*VS:777*RES:OK*SIG:', ['RES' => 'ERROR', 'VS' => '777', 'message' => 'Wrong sign']];
        yield 'no signature' => ['BID:42*VS:777*RES:OK', ['RES' => 'ERROR', 'VS' => '777', 'message' => 'Missing signature']];
        yield 'garbage' => ['garbage', ['RES' => 'ERROR', 'message' => 'Missing signature']];
        yield 'signature only' => ['*SIG:' . self::SIG_OK, ['RES' => 'ERROR', 'message' => 'Wrong BID']];
        yield 'empty' => ['', ['RES' => 'ERROR', 'message' => 'Missing responseString']];
        yield 'array' => [['BID:42'], ['RES' => 'ERROR', 'message' => 'Missing responseString']];
        yield 'missing RES' => ['BID:42*VS:777*SIG:CF1FDCF1E2D3F3E05C39DCC44B55EE87', ['RES' => 'ERROR', 'VS' => '777', 'message' => 'Missing RES']];
        yield 'missing VS' => ['BID:42*RES:OK*SIG:89982867A3C31B52F4E75A5C31C79033', ['RES' => 'ERROR', 'message' => 'Missing VS']];
    }

    /**
     * @param array<string, string> $expected
     */
    #[DataProvider('rejected')]
    public function testRejectedResponse(mixed $responseString, array $expected): void
    {
        $response = $this->complete($responseString);

        self::assertFalse($response->isSuccessful());
        self::assertSame($expected, $response->getData());
        self::assertSame('ERROR', $response->getRes());
        self::assertSame($expected['VS'] ?? null, $response->getVs());
        self::assertSame($expected['message'], $response->getMessage());
    }

    public function testMissingResponseString(): void
    {
        $response = $this->gateway()->completePurchase()->send();

        self::assertFalse($response->isSuccessful());
        self::assertSame(['RES' => 'ERROR', 'message' => 'Missing responseString'], $response->getData());
    }

    public function testInvalidKeyThrows(): void
    {
        $gateway = $this->gateway(['responseString' => 'BID:42*VS:777*RES:OK*SIG:' . self::SIG_OK]);

        $this->expectException(InvalidKeyException::class);

        $gateway->completePurchase(['key1' => ''])->send();
    }
}
