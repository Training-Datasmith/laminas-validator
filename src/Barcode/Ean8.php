<?php

declare (strict_types=1);
namespace Laminas\Validator\Barcode;

use function strlen;
/** @psalm-import-type AllowedLength from AdapterInterface */
final class Ean8 implements Adapter_Interface
{
    public function has_valid_length(string $value): bool
    {
        return strlen($value) === 7 || strlen($value) === 8;
    }
    public function has_valid_characters(string $value): bool
    {
        return Util::string_matches_alphabet($value, '01234567890');
    }
    public function has_valid_checksum(string $value): bool
    {
        if (strlen($value) === 7) {
            return true;
        }
        return Util::gtin($value);
    }
    public function get_length(): array
    {
        return [7, 8];
    }
}