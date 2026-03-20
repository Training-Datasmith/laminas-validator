<?php

declare (strict_types=1);
namespace Laminas\Validator\Barcode;

use function in_array;
use function is_numeric;
use function strlen;
final class Postnet implements Adapter_Interface
{
    private const LENGTH = [6, 7, 10, 12];
    public function has_valid_length(string $value): bool
    {
        return in_array(strlen($value), self::LENGTH, true);
    }
    public function has_valid_characters(string $value): bool
    {
        return is_numeric($value);
    }
    public function has_valid_checksum(string $value): bool
    {
        return Util::postnet($value);
    }
    public function get_length(): array
    {
        return self::LENGTH;
    }
}