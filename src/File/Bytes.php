<?php

declare (strict_types=1);
namespace Laminas\Validator\File;

use function assert;
use function ctype_digit;
use function is_numeric;
use function is_string;
use const PHP_INT_MAX;
use function round;
use function strtoupper;
use function substr;
use function trim;
/**
 * @internal
 *
 * @psalm-immutable
 */
final readonly class Bytes
{
    private function __construct(public int $bytes)
    {
    }
    public static function from_integer(int $bytes): self
    {
        return new self($bytes);
    }
    /**
     * Format filesize in bytes to an SI Unit
     */
    public function to_si_unit(): string
    {
        $size = $this->bytes;
        $sizes = ['B', 'kB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB'];
        for ($i = 0; $size >= 1024 && $i < 9; $i++) {
            $size /= 1024;
        }
        $suffix = $sizes[$i] ?? null;
        assert(is_string($suffix));
        return round($size, 2) . $suffix;
    }
    /**
     * Create a new instance from an SI unit string such as "10 GB"
     */
    public static function from_si_unit(string $size): self
    {
        if (ctype_digit($size)) {
            return self::from_integer((int) $size);
        }
        $type = trim(substr($size, -2, 1));
        $value = substr($size, 0, -1);
        if (!is_numeric($value)) {
            $value = trim(substr($value, 0, -1));
        }
        assert(is_numeric($value));
        switch (strtoupper($type)) {
            case 'Y':
            case 'Z':
                //$value *= 1024 ** 8;
                $value = PHP_INT_MAX;
                break;
            case 'E':
                if ($value > 7) {
                    $value = PHP_INT_MAX;
                    break;
                }
                $value *= 1024 ** 6;
                break;
            case 'P':
                $value *= 1024 ** 5;
                break;
            case 'T':
                $value *= 1024 ** 4;
                break;
            case 'G':
                $value *= 1024 ** 3;
                break;
            case 'M':
                $value *= 1024 ** 2;
                break;
            case 'K':
                $value *= 1024;
                break;
            default:
                break;
        }
        return self::from_integer((int) $value);
    }
}