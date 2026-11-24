<?php

declare(strict_types=1);

namespace Omnipay\Viamo\Tests\Message;

use Omnipay\Common\Exception\InvalidRequestException;
use Omnipay\Viamo\Gateway;
use Omnipay\Viamo\Message\WebhookResponse;
use Omnipay\Viamo\Tests\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;

final class WebhookRequestTest extends TestCase
{
    // openssl CLI signature with KEY2 of "R1" . "777" . "E2E" . "OK" . "10.00" . "555"
    private const string SIG_FULL = 'D07E3492E35D34AC385D161F31B8100D';

    /**
     * @return array<string, mixed>
     */
    private function notification(): array
    {
        return [
            'notificationId' => 'N1',
            'payment' => ['rid' => 'R1', 'vs' => '777', 'e2e' => 'E2E', 'result' => 'OK', 'amount' => '10.00', 'id' => 555],
            'signature' => ['sign' => self::SIG_FULL],
        ];
    }

    /**
     * @param array<mixed>|null $json
     */
    private function webhook(?array $json): WebhookResponse
    {
        return $this->gateway()->webhook(['json' => $json])->send();
    }

    public function testSuccessfulPayment(): void
    {
        $response = $this->webhook($this->notification());

        self::assertTrue($response->isSuccessful());
        self::assertFalse($response->isRedirect());
        self::assertSame('777', $response->getVs());
        self::assertSame('555', $response->getPaymentId());
        self::assertSame('N1', $response->getNotificationId());
        self::assertSame('10.00', $response->getAmount());
        self::assertNull($response->errorMessage());
        self::assertNull($response->getMessage());
        self::assertSame(['success' => true, 'vs' => '777', 'payment_id' => 555, 'notification_id' => 'N1', 'amount' => '10.00'], $response->getData());
    }

    public function testLowercaseSignatureIsAccepted(): void
    {
        $json = $this->notification();
        $json['signature'] = ['sign' => strtolower(self::SIG_FULL)];

        self::assertTrue($this->webhook($json)->isSuccessful());
    }

    public function testOptionalFieldsAreLeftOutOfTheSignature(): void
    {
        // "" . "" . "" . "OK" . "3" . "7"
        $response = $this->webhook([
            'payment' => ['vs' => '', 'result' => 'OK', 'amount' => '3', 'id' => '7'],
            'signature' => ['sign' => '46DD27CD834BEA0E31607048320863BA'],
        ]);

        self::assertTrue($response->isSuccessful());
        self::assertSame('', $response->getVs());
        self::assertSame('7', $response->getPaymentId());
        self::assertNull($response->getNotificationId());
    }

    public function testMissingVs(): void
    {
        $response = $this->webhook([
            'payment' => ['result' => 'OK', 'amount' => '3', 'id' => '7'],
            'signature' => ['sign' => '46DD27CD834BEA0E31607048320863BA'],
        ]);

        self::assertTrue($response->isSuccessful());
        self::assertNull($response->getVs());
    }

    public function testFailedResult(): void
    {
        // "777" . "FAIL" . "1.5" . "556"
        $response = $this->webhook([
            'notificationId' => 9,
            'payment' => ['vs' => '777', 'result' => 'FAIL', 'amount' => 1.5, 'id' => '556'],
            'signature' => ['sign' => 'A768A26D6EFE20D94AC5160620F9AD69'],
        ]);

        self::assertFalse($response->isSuccessful());
        self::assertSame(['success' => false, 'error' => 'Invalid result', 'vs' => '777', 'payment_id' => '556', 'notification_id' => 9, 'amount' => '1.5'], $response->getData());
        self::assertSame('1.5', $response->getAmount());
        self::assertSame('Invalid result', $response->errorMessage());
        self::assertSame('Invalid result', $response->getMessage());
        self::assertSame('777', $response->getVs());
        self::assertSame('556', $response->getPaymentId());
        self::assertSame('9', $response->getNotificationId());
    }

    /**
     * @return iterable<string, array{mixed}>
     */
    public static function wrongSignatures(): iterable
    {
        yield 'other signature' => ['46DD27CD834BEA0E31607048320863BA'];
        yield 'truncated' => [substr(self::SIG_FULL, 0, 31)];
        yield 'empty' => [''];
        yield 'array' => [[self::SIG_FULL]];
        yield 'integer' => [1];
    }

    #[DataProvider('wrongSignatures')]
    public function testWrongSignature(mixed $signature): void
    {
        $json = $this->notification();
        $json['signature'] = ['sign' => $signature];

        $response = $this->webhook($json);

        self::assertFalse($response->isSuccessful());
        self::assertSame(['success' => false, 'error' => 'Wrong signature', 'vs' => '777', 'payment_id' => 555, 'notification_id' => 'N1'], $response->getData());
        self::assertNull($response->getAmount());
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function tamperedFields(): iterable
    {
        yield 'rid' => ['rid', 'R2'];
        yield 'vs' => ['vs', '778'];
        yield 'e2e' => ['e2e', 'E2F'];
        yield 'amount' => ['amount', '100.00'];
        yield 'id' => ['id', '556'];
    }

    #[DataProvider('tamperedFields')]
    public function testEverySignedFieldIsChecked(string $field, string $value): void
    {
        $json = $this->notification();
        self::assertIsArray($json['payment']);
        $json['payment'][$field] = $value;

        self::assertSame('Wrong signature', $this->webhook($json)->errorMessage());
    }

    /**
     * @return iterable<string, array{array<mixed>, string}>
     */
    public static function incomplete(): iterable
    {
        $payment = ['vs' => '777', 'result' => 'OK', 'amount' => '1', 'id' => '1'];
        $signature = ['sign' => self::SIG_FULL];

        yield 'no payment' => [['signature' => $signature], 'Missing payment data'];
        yield 'payment is not an array' => [['payment' => 'x', 'signature' => $signature], 'Missing payment parameter'];
        yield 'no result' => [['payment' => array_diff_key($payment, ['result' => 1]), 'signature' => $signature], 'Missing payment parameter'];
        yield 'no amount' => [['payment' => array_diff_key($payment, ['amount' => 1]), 'signature' => $signature], 'Missing payment parameter'];
        yield 'no id' => [['payment' => array_diff_key($payment, ['id' => 1]), 'signature' => $signature], 'Missing payment parameter'];
        yield 'no signature' => [['payment' => $payment], 'Missing signature'];
        yield 'signature is not an array' => [['payment' => $payment, 'signature' => self::SIG_FULL], 'Missing signature'];
        yield 'signature without sign' => [['payment' => $payment, 'signature' => []], 'Missing signature'];
    }

    /**
     * @param array<mixed> $json
     */
    #[DataProvider('incomplete')]
    public function testIncompleteNotification(array $json, string $error): void
    {
        $response = $this->webhook($json);

        self::assertFalse($response->isSuccessful());
        self::assertSame(['success' => false, 'error' => $error], $response->getData());
        self::assertNull($response->getVs());
        self::assertNull($response->getPaymentId());
        self::assertNull($response->getNotificationId());
        self::assertNull($response->getAmount());
    }

    public function testJsonIsRequired(): void
    {
        self::assertThrows(InvalidRequestException::class, 'The json parameter is required', fn(): WebhookResponse => $this->webhook(null));
    }

    /**
     * @return iterable<string, array{string, ?string}>
     */
    public static function requiredParameters(): iterable
    {
        foreach (['bid', 'key2'] as $parameter) {
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
            fn(): WebhookResponse => $this->gateway()->webhook(['json' => $this->notification(), $parameter => $value])->send(),
        );
    }

    public function testThroughTheGatewayDefaultsAnEmptyBidIsRejected(): void
    {
        $gateway = new Gateway();
        $gateway->initialize(['key2' => self::KEY2]);

        self::assertThrows(InvalidRequestException::class, 'The bid parameter is required', static fn(): WebhookResponse => $gateway->webhook(['json' => []])->send());
    }

    public function testKey1IsNotRequired(): void
    {
        self::assertTrue($this->gateway()->webhook(['json' => $this->notification(), 'key1' => null])->send()->isSuccessful());
    }

    public function testJsonAccessors(): void
    {
        $request = $this->gateway()->webhook();

        self::assertNull($request->getJson());
        self::assertSame($request, $request->setJson(['a' => 1]));
        self::assertSame(['a' => 1], $request->getJson());
    }
}
