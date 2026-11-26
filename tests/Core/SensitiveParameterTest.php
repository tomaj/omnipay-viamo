<?php

declare(strict_types=1);

namespace Omnipay\Viamo\Tests\Core;

use Omnipay\Viamo\Core\CredentialsParameters;
use Omnipay\Viamo\Core\ViamoSign;
use Omnipay\Viamo\Exception\InvalidKeyException;
use Omnipay\Viamo\Gateway;
use Omnipay\Viamo\Message\CompletePurchaseRequest;
use Omnipay\Viamo\Message\PurchaseRequest;
use Omnipay\Viamo\Message\WebhookRequest;
use Omnipay\Viamo\Tests\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * The merchant keys are redacted from exception traces with #[\SensitiveParameter] (#33).
 */
final class SensitiveParameterTest extends TestCase
{
    /**
     * @return iterable<string, array{class-string, string, string}>
     */
    public static function sensitiveParameters(): iterable
    {
        yield 'ViamoSign::sign' => [ViamoSign::class, 'sign', 'secret'];
        yield 'ViamoSign::verify' => [ViamoSign::class, 'verify', 'secret'];
        foreach ([CredentialsParameters::class, Gateway::class, PurchaseRequest::class, CompletePurchaseRequest::class, WebhookRequest::class] as $class) {
            yield $class . '::setKey1' => [$class, 'setKey1', 'value'];
            yield $class . '::setKey2' => [$class, 'setKey2', 'value'];
        }
    }

    /**
     * @param class-string $class
     */
    #[DataProvider('sensitiveParameters')]
    public function testParameterIsSensitive(string $class, string $method, string $parameter): void
    {
        $found = array_values(array_filter(
            new \ReflectionMethod($class, $method)->getParameters(),
            static fn(\ReflectionParameter $p): bool => $p->getName() === $parameter,
        ));

        self::assertCount(1, $found);
        self::assertCount(1, $found[0]->getAttributes(\SensitiveParameter::class));
    }

    /**
     * A key read from an environment file with its trailing newline is rejected; the trace of the exception must not
     * show it, for the return URL (verify() and sign() on the stack) and for the payment request (sign()).
     */
    public function testInvalidKeyExceptionTraceDoesNotContainTheKey(): void
    {
        $key = self::KEY1 . "\n";

        // php.ini-production (and setup-php in CI) drops all arguments from traces; record them like a development setup
        $ignoreArgs = ini_set('zend.exception_ignore_args', '0');
        try {
            $traces = [
                $this->traceOf(fn(): mixed => $this->gateway(['responseString' => 'BID:42*VS:777*RES:OK*SIG:04B15D742A42AD489F05F2174C32DBBD'])->completePurchase(['key1' => $key])->send()),
                $this->traceOf(fn(): mixed => $this->gateway()->purchase(['key1' => $key, 'amount' => '10.00', 'currency' => 'EUR', 'vs' => '777'])->send()),
            ];
        } finally {
            ini_set('zend.exception_ignore_args', (string) $ignoreArgs);
        }

        self::assertStringContainsString('ViamoSign->verify(', $traces[0]);
        foreach ($traces as $trace) {
            self::assertStringContainsString('ViamoSign->sign(', $trace);
            self::assertStringContainsString('Object(SensitiveParameterValue)', $trace);
            self::assertStringNotContainsString(self::KEY1, $trace);
        }
    }

    /**
     * @return string getTraceAsString() of the InvalidKeyException
     */
    private function traceOf(\Closure $callback): string
    {
        try {
            $callback();
        } catch (InvalidKeyException $exception) {
            return $exception->getTraceAsString();
        }
        self::fail('InvalidKeyException expected');
    }
}
