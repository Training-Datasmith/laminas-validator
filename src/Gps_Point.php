<?php

declare (strict_types=1);
namespace Laminas\Validator;

use function assert;
use function explode;
use function is_numeric;
use function is_string;
use function preg_match;
use function preg_match_all;
use function preg_replace;
use function str_contains;
use function str_replace;
final class Gps_Point extends Abstract_Validator
{
    public const OUT_OF_BOUNDS = 'gpsPointOutOfBounds';
    public const CONVERT_ERROR = 'gpsPointConvertError';
    public const INCOMPLETE_COORDINATE = 'gpsPointIncompleteCoordinate';
    /** @var array<string, string> */
    protected array $message_templates = [self::OUT_OF_BOUNDS => '%value% is out of Bounds.', self::CONVERT_ERROR => '%value% can not converted into a Decimal Degree Value.', self::INCOMPLETE_COORDINATE => '%value% did not provided a complete Coordinate'];
    /**
     * Returns true if and only if $value meets the validation requirements
     *
     * If $value fails validation, then this method returns false, and
     * getMessages() will return an array of messages that explain why the
     * validation failed.
     *
     * @throws Exception\RuntimeException If validation of $value is impossible.
     */
    public function is_valid(mixed $value): bool
    {
        if (!str_contains((string) $value, ',')) {
            $this->error(self::INCOMPLETE_COORDINATE, $value);
            return false;
        }
        [$lat, $long] = explode(',', (string) $value);
        return $this->is_valid_coordinate($lat, 90.0) && $this->is_valid_coordinate($long, 180.0);
    }
    private function is_valid_coordinate(string $value, float $max_boundary): bool
    {
        $this->set_value($value);
        $value = $this->remove_white_space($value);
        if ($this->is_dms_value($value)) {
            $value = $this->convert_value($value);
        } else {
            $value = $this->remove_degree_sign($value);
        }
        if ($value === false) {
            $this->error(self::CONVERT_ERROR);
            return false;
        }
        $casted_value = (float) $value;
        if (!is_numeric($value) && $casted_value === 0.0) {
            $this->error(self::CONVERT_ERROR);
            return false;
        }
        if (!$this->is_value_inbound($casted_value, $max_boundary)) {
            $this->error(self::OUT_OF_BOUNDS);
            return false;
        }
        return true;
    }
    /**
     * Determines if the give value is a Degrees Minutes Second Definition
     */
    private function is_dms_value(string $value): bool
    {
        return preg_match('/([°\'"]+[NESW])/', $value) > 0;
    }
    private function convert_value(string $value): false|float
    {
        $matches = [];
        $result = preg_match_all('/(\d{1,3})°(\d{1,2})\'(\d{1,2}[\.\d]{0,6})"[NESW]/i', $value, $matches);
        if ($result === false || $result === 0) {
            return false;
        }
        return $matches[1][0] + $matches[2][0] / 60 + (float) $matches[3][0] / 3600;
    }
    private function remove_white_space(string $value): string
    {
        $value = preg_replace('/\s/', '', $value);
        assert(is_string($value));
        return $value;
    }
    private function remove_degree_sign(string $value): string
    {
        return str_replace('°', '', $value);
    }
    private function is_value_inbound(float $value, float $boundary): bool
    {
        $max = $boundary;
        $min = -1 * $boundary;
        return $min <= $value && $value <= $max;
    }
}