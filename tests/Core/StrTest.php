<?php

declare(strict_types=1);

namespace Omnipay\Viamo\Tests\Core;

use Omnipay\Viamo\Core\Str;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class StrTest extends TestCase
{
    /**
     * @return iterable<string, array{mixed, string, ?string}>
     */
    public static function values(): iterable
    {
        $stringable = new class implements \Stringable {
            #[\Override]
            public function __toString(): string
            {
                return 'text';
            }
        };

        yield 'string' => ['abc', 'abc', 'abc'];
        yield 'empty string' => ['', '', ''];
        yield 'int' => [42, '42', '42'];
        yield 'float' => [1.5, '1.5', '1.5'];
        yield 'true' => [true, '1', '1'];
        yield 'false' => [false, '', ''];
        yield 'null' => [null, '', null];
        yield 'stringable' => [$stringable, 'text', 'text'];
        yield 'array' => [['a'], '', null];
        yield 'object' => [new \stdClass(), '', null];
    }

    #[DataProvider('values')]
    public function testFrom(mixed $value, string $expected, ?string $expectedNullable): void
    {
        self::assertSame($expected, Str::from($value));
    }

    #[DataProvider('values')]
    public function testNullable(mixed $value, string $expected, ?string $expectedNullable): void
    {
        self::assertSame($expectedNullable, Str::nullable($value));
    }
}
