<?php

declare (strict_types=1);
namespace Laminas\Validator\Barcode;

use function strlen;
/** @psalm-import-type AllowedLength from AdapterInterface */
final class Ean5 implements Adapter_Interface
{
    public function has_valid_length(string $value): bool
    {
        return strlen($value) === 5;
    }
    public function has_valid_characters(string $value): bool
    {
        return Util::string_matches_alphabet($value, '0123456789');
    }
    public function has_valid_checksum(string $value): bool
    {
        return true;
    }
    public function get_length(): int
    {
        return 5;
    }
}