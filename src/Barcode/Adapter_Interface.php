<?php

declare (strict_types=1);
namespace Laminas\Validator\Barcode;

/**
 * @psalm-type AllowedLength = int|list<int>|'even'|'odd'|null
 */
interface Adapter_Interface
{
    /**
     * Checks the length of a barcode
     */
    public function has_valid_length(string $value): bool;
    /**
     * Checks for allowed characters within the barcode
     */
    public function has_valid_characters(string $value): bool;
    /**
     * Validates the checksum
     */
    public function has_valid_checksum(string $value): bool;
    /**
     * Returns the allowed barcode length
     *
     * @return AllowedLength
     */
    public function get_length();
}