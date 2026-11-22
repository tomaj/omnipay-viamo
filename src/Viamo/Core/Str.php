<?php

namespace Omnipay\Viamo\Core;

/**
 * @internal
 */
final class Str
{
    /**
     * Converts a loosely typed parameter value to string the same way string interpolation does.
     * Values that cannot be converted (arrays, plain objects) become an empty string.
     */
    public static function from(mixed $value): string
    {
        if ($value === null || is_scalar($value) || $value instanceof \Stringable) {
            return (string) $value;
        }

        return '';
    }
}
