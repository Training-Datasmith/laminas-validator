<?php

declare (strict_types=1);
namespace Laminas\Validator\Barcode;

use function is_numeric;
use function strlen;
final class Code25interleaved implements Adapter_Interface
{
    public function has_valid_length(string $value): bool
    {
        return strlen($value) % 2 === 0;
    }
    public function has_valid_characters(string $value): bool
    {
        return is_numeric($value);
    }
    public function has_valid_checksum(string $value): bool
    {
        return Util::code25($value);
    }
    public function get_length(): string
    {
        return 'even';
    }
}