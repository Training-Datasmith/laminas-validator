<?php

declare (strict_types=1);
namespace Laminas\Validator\Barcode;

use function is_numeric;
use function strlen;
final class Ean18 implements Adapter_Interface
{
    public function has_valid_length(string $value): bool
    {
        return strlen($value) === 18;
    }
    public function has_valid_characters(string $value): bool
    {
        return is_numeric($value);
    }
    public function has_valid_checksum(string $value): bool
    {
        return Util::gtin($value);
    }
    public function get_length(): int
    {
        return 18;
    }
}