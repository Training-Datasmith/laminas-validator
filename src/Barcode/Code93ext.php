<?php

declare (strict_types=1);
namespace Laminas\Validator\Barcode;

final class Code93ext implements Adapter_Interface
{
    public function has_valid_length(string $value): bool
    {
        return true;
    }
    public function has_valid_characters(string $value): bool
    {
        return Util::is_ascii128($value);
    }
    public function has_valid_checksum(string $value): bool
    {
        return true;
    }
    public function get_length(): int
    {
        return -1;
    }
}