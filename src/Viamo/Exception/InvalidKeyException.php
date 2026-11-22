<?php

declare(strict_types=1);

namespace Omnipay\Viamo\Exception;

use Omnipay\Common\Exception\InvalidRequestException;

/**
 * The signing key (`key1` or `key2`) is not a 128-bit key written as 32 hexadecimal characters.
 */
final class InvalidKeyException extends InvalidRequestException implements ViamoException {}
