<?php

declare (strict_types=1);
namespace Laminas\Validator;

use function array_combine;
use function array_count_values;
use function array_map;
use function ceil;
use DateInterval;
use DateTime;
use DateTimeImmutable;
use DateTimeInterface;
use DateTimeZone;
use function explode;
use function floor;
use function in_array;
use function is_array;
use function is_string;
use Laminas\Translator\Translator_Interface;
use Laminas\Validator\Exception\InvalidArgumentException;
use function max;
use function min;
use const PHP_INT_MAX;
use function preg_match;
use function sprintf;
use function str_starts_with;
/**
 * @psalm-type OptionsArgument = array{
 *     format?: string|null,
 *     strict?: bool,
 *     baseValue?: string|DateTimeInterface,
 *     step?: string|DateInterval,
 *     messages?: array<string, string>,
 *     translator?: TranslatorInterface|null,
 *     translatorTextDomain?: string|null,
 *     translatorEnabled?: bool,
 *     valueObscured?: bool,
 * }
 */
final class Date_Step extends Date
{
    /**
     * Validity constants
     */
    public const NOT_STEP = 'dateStepNotStep';
    /**
     * Default format constant
     */
    public const FORMAT_DEFAULT = DateTimeInterface::ATOM;
    /**
     * Validation failure message template definitions
     *
     * @var array<string, string>
     */
    protected array $message_templates = [self::INVALID => 'Invalid type given. String, integer, array or DateTime expected', self::INVALID_DATE => 'The input does not appear to be a valid date', self::FALSEFORMAT => "The input does not fit the date format '%format%'", self::NOT_STEP => 'The input is not a valid step'];
    /**
     * Optional base date value
     */
    protected readonly DateTimeInterface $base_value;
    /**
     * Date step interval (defaults to 1 day).
     * Uses the DateInterval specification.
     */
    protected readonly DateInterval $step;
    /**
     * Set default options for this instance
     *
     * @param OptionsArgument $options
     */
    public function __construct(array $options = [])
    {
        $step = $options['step'] ?? 'P1D';
        $base_value = $options['baseValue'] ?? null;
        unset($options['step'], $options['baseValue']);
        parent::__construct($options);
        if (!$step instanceof DateInterval) {
            $step = new DateInterval($step);
        }
        $this->step = $step;
        if (!$base_value instanceof DateTimeInterface && is_string($base_value)) {
            $base_value = $this->convert_to_date_time($base_value, false);
        }
        if (!$base_value instanceof DateTimeInterface) {
            $base_value = DateTimeImmutable::create_from_format(DateTimeInterface::ATOM, '1970-01-01T00:00:00Z');
        }
        if ($base_value === false) {
            throw new InvalidArgumentException('The given base value is not in the expected format, or is an invalid date time string');
        }
        $this->base_value = $base_value;
    }
    /**
     * Supports formats with ISO week (W) definitions
     */
    protected function convert_string(string $value, bool $add_errors = true): false|DateTimeImmutable
    {
        // Custom week format support
        if (str_starts_with($this->format, 'Y-\WW') && preg_match('/^([0-9]{4})-W([0-9]{2})/', $value, $matches)) {
            $date = new DateTimeImmutable();
            $date = $date->set_iso_date((int) $matches[1], (int) $matches[2]);
        } else {
            $date = DateTimeImmutable::create_from_format($this->format, $value, new DateTimeZone('UTC'));
        }
        // Invalid dates can show up as warnings (ie. "2007-02-99")
        // and still return a DateTime object.
        $errors = DateTime::get_last_errors();
        if (is_array($errors) && $errors['warning_count'] > 0) {
            if ($add_errors) {
                $this->error(self::FALSEFORMAT);
            }
            return false;
        }
        return $date;
    }
    /**
     * Returns true if a date is within a valid step
     *
     * @throws InvalidArgumentException
     */
    public function is_valid(mixed $value): bool
    {
        if (!parent::is_valid($value)) {
            return false;
        }
        $value_date = $this->convert_to_date_time($value, false);
        // avoid duplicate errors
        $base_date = $this->convert_to_date_time($this->base_value, false);
        if (false === $value_date || false === $base_date) {
            return false;
        }
        $step = $this->step;
        // Same date?
        // phpcs:ignore SlevomatCodingStandard.Operators.DisallowEqualOperators.DisallowedEqualOperator
        if ($value_date == $base_date) {
            return true;
        }
        // Optimization for simple intervals.
        // Handle intervals of just one date or time unit.
        $interval_parts = explode('|', $step->format('%y|%m|%d|%h|%i|%s'));
        $interval_parts = array_map(intval(...), $interval_parts);
        $part_counts = array_count_values($interval_parts);
        $unit_keys = ['years', 'months', 'days', 'hours', 'minutes', 'seconds'];
        $interval_parts = array_combine($unit_keys, $interval_parts);
        // Get absolute time difference to avoid special cases of missing/added time
        $absolute_value_date = new DateTime($value_date->format('Y-m-d H:i:s'), new DateTimeZone('UTC'));
        $absolute_base_date = new DateTime($base_date->format('Y-m-d H:i:s'), new DateTimeZone('UTC'));
        $time_diff = $absolute_value_date->diff($absolute_base_date, true);
        $diff_parts = array_map(intval(...), explode('|', $time_diff->format('%y|%m|%d|%h|%i|%s')));
        $diff_parts = array_combine($unit_keys, $diff_parts);
        if (5 === $part_counts[0]) {
            // Find the unit with the non-zero interval
            $interval_unit = 'days';
            $step_value = 1;
            foreach ($interval_parts as $key => $value) {
                if (0 !== $value) {
                    $interval_unit = $key;
                    $step_value = $value;
                    break;
                }
            }
            // Check date units
            if (in_array($interval_unit, ['years', 'months', 'days'])) {
                switch ($interval_unit) {
                    case 'years':
                        if (0 === $diff_parts['months'] && 0 === $diff_parts['days'] && 0 === $diff_parts['hours'] && 0 === $diff_parts['minutes'] && 0 === $diff_parts['seconds']) {
                            if ($diff_parts['years'] % $step_value === 0) {
                                return true;
                            }
                        }
                        break;
                    case 'months':
                        if (0 === $diff_parts['days'] && 0 === $diff_parts['hours'] && 0 === $diff_parts['minutes'] && 0 === $diff_parts['seconds']) {
                            $months = $diff_parts['years'] * 12 + $diff_parts['months'];
                            if ($months % $step_value === 0) {
                                return true;
                            }
                        }
                        break;
                    case 'days':
                        if (0 === $diff_parts['hours'] && 0 === $diff_parts['minutes'] && 0 === $diff_parts['seconds']) {
                            $days = (int) $time_diff->format('%a');
                            // Total days
                            if ($days % $step_value === 0) {
                                return true;
                            }
                        }
                        break;
                }
                $this->error(self::NOT_STEP);
                return false;
            }
            // Check time units
            if (in_array($interval_unit, ['hours', 'minutes', 'seconds'])) {
                // Simple test if $stepValue is 1.
                if (1 === $step_value) {
                    if ('hours' === $interval_unit && 0 === $diff_parts['minutes'] && 0 === $diff_parts['seconds']) {
                        return true;
                    }
                    if ('minutes' === $interval_unit && 0 === $diff_parts['seconds']) {
                        return true;
                    }
                    if ('seconds' === $interval_unit) {
                        return true;
                    }
                    $this->error(self::NOT_STEP);
                    return false;
                }
                // Simple test for same day, when using default baseDate
                if ($base_date->format('Y-m-d') === $value_date->format('Y-m-d') && $base_date->format('Y-m-d') === '1970-01-01') {
                    switch ($interval_unit) {
                        case 'hours':
                            if (0 === $diff_parts['minutes'] && 0 === $diff_parts['seconds']) {
                                if ($diff_parts['hours'] % $step_value === 0) {
                                    return true;
                                }
                            }
                            break;
                        case 'minutes':
                            if (0 === $diff_parts['seconds']) {
                                $minutes = $diff_parts['hours'] * 60 + $diff_parts['minutes'];
                                if ($minutes % $step_value === 0) {
                                    return true;
                                }
                            }
                            break;
                        case 'seconds':
                            $seconds = $diff_parts['hours'] * 60 * 60 + $diff_parts['minutes'] * 60 + $diff_parts['seconds'];
                            if ($seconds % $step_value === 0) {
                                return true;
                            }
                            break;
                    }
                    $this->error(self::NOT_STEP);
                    return false;
                }
            }
        }
        return $this->fallback_incremental_iteration_logic($base_date, $value_date, $interval_parts, $diff_parts, $step);
    }
    /**
     * Fall back to slower (but accurate) method for complex intervals.
     * Keep adding steps to the base date until a match is found
     * or until the value is exceeded.
     *
     * This is really slow if the interval is small, especially if the
     * default base date of 1/1/1970 is used. We can skip a chunk of
     * iterations by starting at the lower bound of steps needed to reach
     * the target
     *
     * @param int[] $intervalParts
     * @param int[] $diffParts
     * @throws InvalidArgumentException
     */
    private function fallback_incremental_iteration_logic(DateTimeInterface $base_date, DateTimeInterface $value_date, array $interval_parts, array $diff_parts, DateInterval $step): bool
    {
        [$min_steps, $required_iterations] = $this->compute_min_step_and_required_iterations($interval_parts, $diff_parts);
        $minimum_interval = $this->compute_minimum_interval($interval_parts, $min_steps);
        $is_incremental_stepping = $base_date < $value_date;
        if ($base_date instanceof DateTime) {
            $base_date = DateTimeImmutable::create_from_mutable($base_date);
        }
        for ($offset_iterations = 0; $offset_iterations < $required_iterations; $offset_iterations += 1) {
            if ($is_incremental_stepping) {
                $base_date = $base_date->add($minimum_interval);
            } else {
                $base_date = $base_date->sub($minimum_interval);
            }
        }
        while ($is_incremental_stepping && $base_date < $value_date || !$is_incremental_stepping && $base_date > $value_date) {
            if ($is_incremental_stepping) {
                $base_date = $base_date->add($step);
            } else {
                $base_date = $base_date->sub($step);
            }
            // phpcs:ignore SlevomatCodingStandard.Operators.DisallowEqualOperators.DisallowedEqualOperator
            if ($base_date == $value_date) {
                return true;
            }
        }
        $this->error(self::NOT_STEP);
        return false;
    }
    /**
     * Computes minimum interval to use for iterations while checking steps
     *
     * @param int[] $intervalParts
     * @param int|float $minSteps
     */
    private function compute_minimum_interval(array $interval_parts, $min_steps): DateInterval
    {
        return new DateInterval(sprintf('P%dY%dM%dDT%dH%dM%dS', $interval_parts['years'] * $min_steps, $interval_parts['months'] * $min_steps, $interval_parts['days'] * $min_steps, $interval_parts['hours'] * $min_steps, $interval_parts['minutes'] * $min_steps, $interval_parts['seconds'] * $min_steps));
    }
    /**
     * @param int[] $intervalParts
     * @param int[] $diffParts
     * @return int[] (ordered tuple containing minimum steps and required step iterations
     * @psalm-return array{0: int, 1: int}
     */
    private function compute_min_step_and_required_iterations(array $interval_parts, array $diff_parts): array
    {
        $min_steps = $this->compute_min_steps($interval_parts, $diff_parts);
        // If we use PHP_INT_MAX DateInterval::__construct falls over with a bad format error
        // before we reach the max on 64 bit machines
        $max_integer = min(2 ** 31, PHP_INT_MAX);
        // check for integer overflow and split $minimum interval if needed
        $maximum_interval = max($interval_parts);
        $required_step_iterations = 1;
        if ($min_steps * $maximum_interval > $max_integer) {
            $required_step_iterations = ceil($min_steps * $maximum_interval / $max_integer);
            $min_steps = floor($min_steps / $required_step_iterations);
        }
        return [(int) $min_steps, $min_steps !== 0 ? (int) $required_step_iterations : 0];
    }
    /**
     * Multiply the step interval by the lower bound of steps to reach the target
     *
     * @param int[] $intervalParts
     * @param int[] $diffParts
     * @return float|int
     */
    private function compute_min_steps(array $interval_parts, array $diff_parts)
    {
        $interval_max_seconds = $this->compute_interval_max_seconds($interval_parts);
        return 0 === $interval_max_seconds ? 0 : max(floor($this->compute_diff_min_seconds($diff_parts) / $interval_max_seconds) - 1, 0);
    }
    /**
     * Get upper bound of the given interval in seconds
     * Converts a given `$intervalParts` array into seconds
     *
     * @param int[] $intervalParts
     */
    private function compute_interval_max_seconds(array $interval_parts): int
    {
        return $interval_parts['years'] * 60 * 60 * 24 * 366 + $interval_parts['months'] * 60 * 60 * 24 * 31 + $interval_parts['days'] * 60 * 60 * 24 + $interval_parts['hours'] * 60 * 60 + $interval_parts['minutes'] * 60 + $interval_parts['seconds'];
    }
    /**
     * Get lower bound of difference in secondss
     * Converts a given `$diffParts` array into seconds
     *
     * @param int[] $diffParts
     */
    private function compute_diff_min_seconds(array $diff_parts): int
    {
        return $diff_parts['years'] * 60 * 60 * 24 * 365 + $diff_parts['months'] * 60 * 60 * 24 * 28 + $diff_parts['days'] * 60 * 60 * 24 + $diff_parts['hours'] * 60 * 60 + $diff_parts['minutes'] * 60 + $diff_parts['seconds'];
    }
}