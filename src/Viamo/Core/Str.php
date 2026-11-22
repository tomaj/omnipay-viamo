<?php

declare(strict_types=1);

namespace Omnipay\Viamo\Core;

/**
 * @internal
 */
final class Str
{
    /**
     * Converts a loosely typed value to string the same way string interpolation does.
     * Values that cannot be converted (arrays, plain objects) become an empty string.
     */
    public static function from(mixed $value): string
    {
        if ($value === null || is_scalar($value) || $value instanceof \Stringable) {
            return (string) $value;
        }

        return '';
    }

    /**
     * Like from(), but keeps null (and values that cannot be converted) as null.
     */
    public static function nullable(mixed $value): ?string
    {
        if (is_scalar($value) || $value instanceof \Stringable) {
            return (string) $value;
        }

        return null;
    }
}
