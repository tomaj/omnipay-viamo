<?php

declare(strict_types=1);

namespace Omnipay\Viamo\Tests\Core;

use Omnipay\Common\Exception\InvalidRequestException;
use Omnipay\Viamo\Core\ViamoSign;
use Omnipay\Viamo\Exception\InvalidKeyException;
use Omnipay\Viamo\Exception\ViamoException;
use Omnipay\Viamo\Tests\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;

final class ViamoSignTest extends TestCase
{
    /**
     * Expected values from the openssl CLI (see Omnipay\Viamo\Tests\TestCase).
     *
     * @return iterable<string, array{string, string, string}>
     */
    public static function vectors(): iterable
    {
        yield 'return URL, lowercase key' => ['BID:42*VS:777*RES:OK', '000102030405060708090a0b0c0d0e0f', '04B15D742A42AD489F05F2174C32DBBD'];
        yield 'same input, other key' => ['BID:42*VS:777*RES:OK', '11111111111111111111111111111111', '10B0B82A693F2D6D0B78D77EE77558B3'];
        yield 'same input, uppercase key' => ['BID:42*VS:777*RES:OK', 'F0E1D2C3B4A5968778695A4B3C2D1E0F', 'FAC0E8E3EB3BB2FFDFBE7FD016D25DA1'];
        yield 'empty input' => ['', '11111111111111111111111111111111', '9E852174E3E0459D9C00EEF27378F712'];
        yield 'payment request' => ['QP:1.0*BID:42*AM:10.00*CC:EUR*VS:777*MSG:Order 777', '000102030405060708090a0b0c0d0e0f', '4EBE348B1F40E5598DBF0C109174E6A0'];
        yield 'webhook' => ['R1777E2EOK10.00555', 'F0E1D2C3B4A5968778695A4B3C2D1E0F', 'D07E3492E35D34AC385D161F31B8100D'];
    }

    #[DataProvider('vectors')]
    public function testSignMatchesOpenssl(string $input, string $key, string $expected): void
    {
        self::assertSame($expected, new ViamoSign()->sign($input, $key));
    }

    #[DataProvider('vectors')]
    public function testVerifyAcceptsTheSignatureInEitherCase(string $input, string $key, string $expected): void
    {
        $sign = new ViamoSign();

        self::assertTrue($sign->verify($input, $key, $expected));
        self::assertTrue($sign->verify($input, $key, strtolower($expected)));
    }

    /**
     * @return iterable<string, array{mixed}>
     */
    public static function wrongSignatures(): iterable
    {
        yield 'other signature' => ['10B0B82A693F2D6D0B78D77EE77558B3'];
        yield 'truncated' => ['04B15D742A42AD489F05F2174C32DBB'];
        yield 'one character appended' => ['04B15D742A42AD489F05F2174C32DBBD0'];
        yield 'surrounding whitespace' => [' 04B15D742A42AD489F05F2174C32DBBD'];
        yield 'empty' => [''];
        yield 'null' => [null];
        yield 'integer' => [0];
        yield 'array' => [['04B15D742A42AD489F05F2174C32DBBD']];
    }

    #[DataProvider('wrongSignatures')]
    public function testVerifyRejectsWrongSignatures(mixed $signature): void
    {
        self::assertFalse(new ViamoSign()->verify('BID:42*VS:777*RES:OK', '000102030405060708090a0b0c0d0e0f', $signature));
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function invalidKeys(): iterable
    {
        yield 'empty' => [''];
        yield '31 characters' => ['000102030405060708090a0b0c0d0e0'];
        yield '33 characters' => ['000102030405060708090a0b0c0d0e0f0'];
        yield '256-bit key' => ['000102030405060708090a0b0c0d0e0f000102030405060708090a0b0c0d0e0f'];
        yield 'not hexadecimal' => ['000102030405060708090a0b0c0d0e0g'];
        yield 'trailing newline' => ["000102030405060708090a0b0c0d0e0f\n"];
        yield 'leading newline' => ["\n000102030405060708090a0b0c0d0e0f"];
    }

    #[DataProvider('invalidKeys')]
    public function testSignRejectsAnInvalidKey(string $key): void
    {
        $this->expectException(InvalidKeyException::class);

        new ViamoSign()->sign('BID:42', $key);
    }

    public function testInvalidKeyCanBeCaughtAsAPackageException(): void
    {
        self::assertThrows(
            ViamoException::class,
            'The Viamo key must be 32 hexadecimal characters (a 128-bit key).',
            fn(): string => new ViamoSign()->sign('BID:42', 'nope'),
        );
    }

    public function testInvalidKeyCanBeCaughtAsAnOmnipayException(): void
    {
        $this->expectException(InvalidRequestException::class);

        new ViamoSign()->sign('BID:42', 'nope');
    }

    public function testVerifyRejectsAnInvalidKey(): void
    {
        $this->expectException(InvalidKeyException::class);

        new ViamoSign()->verify('BID:42', 'nope', '04B15D742A42AD489F05F2174C32DBBD');
    }
}
