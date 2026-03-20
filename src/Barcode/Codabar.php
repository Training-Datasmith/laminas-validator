<?php

declare (strict_types=1);
namespace Laminas\Validator\Barcode;

use function strpbrk;
use function substr;
/**
 * @link https://en.wikipedia.org/wiki/Codabar
 */
final class Codabar implements Adapter_Interface
{
    /**
     * Checks for allowed characters
     */
    public function has_valid_characters(string $value): bool
    {
        if (strpbrk($value, 'ABCD') !== false) {
            $first = $value[0];
            if (strpbrk($first, 'ABCD') === false) {
                // Missing start char
                return false;
            }
            $last = substr($value, -1, 1);
            if (strpbrk($last, 'ABCD') === false) {
                // Missing stop char
                return false;
            }
            $value = substr($value, 1, -1);
        } elseif (strpbrk($value, 'TN*E') !== false) {
            $first = $value[0];
            if (strpbrk($first, 'TN*E') === false) {
                // Missing start char
                return false;
            }
            $last = substr($value, -1, 1);
            if (strpbrk($last, 'TN*E') === false) {
                // Missing stop char
                return false;
            }
            $value = substr($value, 1, -1);
        }
        return Util::string_matches_alphabet($value, '0123456789-$:/.+');
    }
    public function has_valid_length(string $value): bool
    {
        return true;
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