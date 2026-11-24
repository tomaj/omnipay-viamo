<?php

declare(strict_types=1);

namespace Omnipay\Viamo\Tests;

use Omnipay\Viamo\Core\ViamoSign;
use Omnipay\Viamo\Gateway;
use Omnipay\Viamo\Message\CompletePurchaseResponse;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Runs every snippet the documentation imports from docs/snippets/ against the current code.
 */
final class DocsSnippetsTest extends TestCase
{
    private const string SNIPPETS = __DIR__ . '/../docs/snippets/';

    /** @var array<string, string|false> */
    private array $environment = [];

    /** @var array<mixed> */
    private array $get = [];

    #[\Override]
    protected function setUp(): void
    {
        foreach (['VIAMO_BID' => self::BID, 'VIAMO_KEY1' => self::KEY1, 'VIAMO_KEY2' => self::KEY2, 'VIAMO_TEST_MODE' => '0'] as $name => $value) {
            $this->environment[$name] = getenv($name);
            putenv("{$name}={$value}");
        }
        $this->get = $_GET;
    }

    #[\Override]
    protected function tearDown(): void
    {
        foreach ($this->environment as $name => $value) {
            putenv($value === false ? $name : "{$name}={$value}");
        }
        $_GET = $this->get;
    }

    public function testEverySnippetIsTested(): void
    {
        $paths = glob(self::SNIPPETS . '*.php');
        self::assertIsArray($paths);
        $files = array_map(basename(...), $paths);
        sort($files);

        self::assertSame(['complete-purchase.php', 'errors.php', 'gateway.php', 'purchase.php', 'signature.php', 'test-mode.php', 'testing-phpunit.php', 'testing-return-url.php', 'testing-webhook.php', 'webhook.php'], $files);
    }

    public function testGateway(): void
    {
        $gateway = $this->execute('gateway.php');

        self::assertInstanceOf(Gateway::class, $gateway);
        self::assertSame(self::BID, $gateway->getBid());
        self::assertSame(self::KEY1, $gateway->getKey1());
        self::assertSame(self::KEY2, $gateway->getKey2());
        self::assertFalse($gateway->getTestMode());
    }

    public function testPurchase(): void
    {
        $output = $this->capture('purchase.php');

        self::assertMatchesRegularExpression('~^<img alt="Viamo QR code" src="data:image/png;base64,[A-Za-z0-9+/]+=*">~', $output);
        self::assertStringContainsString(
            '<a href="viamo://?requestString=QP:1.0*BID:42*AM:10.00*CC:EUR*VS:2026000123*MSG:Order 2026000123*RURL:https://shop.example/viamo/return*SIG:',
            $output,
        );
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function returnUrls(): iterable
    {
        $sign = new ViamoSign();
        yield 'paid' => ['BID:42*VS:777*RES:OK*SIG:' . $sign->sign('BID:42*VS:777*RES:OK', self::KEY1), 'Payment for order 777 received.'];
        yield 'failed' => ['BID:42*VS:777*RES:FAIL*SIG:' . $sign->sign('BID:42*VS:777*RES:FAIL', self::KEY1), 'Payment failed: FAIL'];
        yield 'tampered' => ['BID:42*VS:777*RES:OK*SIG:' . $sign->sign('BID:42*VS:777*RES:FAIL', self::KEY1), 'Payment failed: Wrong sign'];
    }

    #[DataProvider('returnUrls')]
    public function testCompletePurchase(string $responseString, string $expected): void
    {
        $_GET = ['responseString' => $responseString];

        self::assertSame($expected, $this->capture('complete-purchase.php'));
    }

    public function testWebhookWithoutBody(): void
    {
        // php://input is empty on the CLI: the snippet answers 400 without throwing
        self::assertNull($this->execute('webhook.php'));
    }

    public function testTestMode(): void
    {
        self::assertStringStartsWith('viamo-staging://?requestString=QP:1.0*BID:42*AM:1.00*CC:EUR*VS:1*SIG:', $this->capture('test-mode.php'));
    }

    public function testSignature(): void
    {
        self::assertSame('04B15D742A42AD489F05F2174C32DBBD', $this->capture('signature.php'));
    }

    public function testTestingReturnUrl(): void
    {
        $url = $this->capture('testing-return-url.php');
        $query = [];
        parse_str((string) parse_url($url, PHP_URL_QUERY), $query);

        self::assertStringStartsWith('https://shop.example/viamo/return?responseString=BID%3A42%2AVS%3A2026000123%2ARES%3AOK%2ASIG%3A', $url);
        // signature computed with the openssl CLI
        self::assertSame(['responseString' => 'BID:42*VS:2026000123*RES:OK*SIG:88F0AC8F3D9B94BB1C4241EDC85D4E40'], $query);
        $response = $this->gateway($query)->completePurchase()->send();
        self::assertTrue($response->isSuccessful());
    }

    public function testTestingWebhook(): void
    {
        $json = json_decode($this->capture('testing-webhook.php'), true);
        self::assertIsArray($json);

        $notification = $this->gateway()->webhook(['json' => $json])->send();
        self::assertTrue($notification->isSuccessful());
        self::assertSame('2026000123', $notification->getVs());
        self::assertSame('555001', $notification->getPaymentId());
        self::assertSame('N-1001', $notification->getNotificationId());
    }

    public function testTestingPhpunit(): void
    {
        $result = $this->execute('testing-phpunit.php');

        self::assertInstanceOf(CompletePurchaseResponse::class, $result);
        self::assertTrue($result->isSuccessful());
        self::assertSame('2026000123', $result->getVs());
    }

    public function testErrors(): void
    {
        $log = ini_set('error_log', '/dev/null');
        try {
            self::assertSame(1, $this->execute('errors.php'));
        } finally {
            ini_set('error_log', (string) $log);
        }
    }

    private function execute(string $snippet): mixed
    {
        return (static fn(): mixed => require self::SNIPPETS . $snippet)();
    }

    private function capture(string $snippet): string
    {
        ob_start();
        try {
            $this->execute($snippet);
        } finally {
            $output = (string) ob_get_clean();
        }

        return $output;
    }
}
