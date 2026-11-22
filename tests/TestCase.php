<?php

declare(strict_types=1);

namespace Omnipay\Viamo\Tests;

use Omnipay\Viamo\Gateway;
use Symfony\Component\HttpFoundation\Request;

/**
 * Keys and signatures in the tests are computed independently with the openssl CLI:
 *
 *     printf '%s' "$input" | openssl dgst -sha256 -binary | head -c 16 \
 *         | openssl enc -aes-128-ecb -nopad -K "$key" | xxd -p -u
 */
abstract class TestCase extends \PHPUnit\Framework\TestCase
{
    protected const string BID = '42';

    protected const string KEY1 = '000102030405060708090a0b0c0d0e0f';

    protected const string KEY2 = 'F0E1D2C3B4A5968778695A4B3C2D1E0F';

    /**
     * @param array<string, mixed> $query the query string of the current request
     */
    protected function gateway(array $query = []): Gateway
    {
        $gateway = new Gateway(null, new Request($query));
        $gateway->initialize([
            'bid' => self::BID,
            'key1' => self::KEY1,
            'key2' => self::KEY2,
        ]);

        return $gateway;
    }

    /**
     * @param class-string<\Throwable> $class
     */
    protected static function assertThrows(string $class, string $message, \Closure $callback): void
    {
        try {
            $callback();
        } catch (\Throwable $e) {
            self::assertInstanceOf($class, $e);
            self::assertSame($message, $e->getMessage());

            return;
        }
        self::fail($class . ' was not thrown');
    }
}
