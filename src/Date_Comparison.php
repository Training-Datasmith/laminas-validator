<?php

declare (strict_types=1);
namespace Laminas\Validator;

use function assert;
use DateTimeImmutable;
use DateTimeInterface;
use DateTimeZone;
use function get_debug_type;
use function is_string;
use Laminas\Translator\Translator_Interface;
use Laminas\Validator\Exception\InvalidArgumentException;
use function preg_match;
/**
 * @psalm-type OptionsArgument = array{
 *     min?: string|DateTimeInterface|null,
 *     max?: string|DateTimeInterface|null,
 *     inclusiveMin?: bool,
 *     inclusiveMax?: bool,
 *     inputFormat?: string|null,
 *     messages?: array<string, string>,
 *     translator?: TranslatorInterface|null,
 *     translatorTextDomain?: string|null,
 *     translatorEnabled?: bool,
 *     valueObscured?: bool,
 * }
 */
final class Date_Comparison extends Abstract_Validator
{
    public const ERROR_INVALID_TYPE = 'invalidType';
    public const ERROR_INVALID_DATE = 'invalidDate';
    public const ERROR_NOT_GREATER_INCLUSIVE = 'notGreaterInclusive';
    public const ERROR_NOT_GREATER = 'notGreater';
    public const ERROR_NOT_LESS_INCLUSIVE = 'notLessInclusive';
    public const ERROR_NOT_LESS = 'notLess';
    /** @var array<string, string> */
    protected array $message_templates = [self::ERROR_INVALID_TYPE => 'Expected a string or a date time instance but received "%type"', self::ERROR_INVALID_DATE => 'Invalid date provided', self::ERROR_NOT_GREATER_INCLUSIVE => 'A date equal to or after %min% is required', self::ERROR_NOT_GREATER => 'A date after %min% is required', self::ERROR_NOT_LESS_INCLUSIVE => 'A date equal to or before %max% is required', self::ERROR_NOT_LESS => 'A date before %max% is required'];
    /** @var array<string, string|array<string, string>> */
    protected array $message_variables = ['type' => 'type', 'min' => 'minString', 'max' => 'maxString'];
    private readonly ?DateTimeInterface $min;
    private readonly ?DateTimeInterface $max;
    private readonly bool $inclusive_min;
    private readonly bool $inclusive_max;
    private readonly ?string $input_format;
    /** Input type used in message variables */
    protected ?string $type = null;
    protected ?string $min_string = null;
    protected ?string $max_string = null;
    /** @param OptionsArgument $options */
    public function __construct(array $options = [])
    {
        parent::__construct($options);
        $this->min = $this->date_instance_bound($options['min'] ?? null);
        $this->max = $this->date_instance_bound($options['max'] ?? null);
        $this->inclusive_min = $options['inclusiveMin'] ?? true;
        $this->inclusive_max = $options['inclusiveMax'] ?? true;
        $this->input_format = $options['inputFormat'] ?? null;
        if ($this->min === null && $this->max === null) {
            throw new InvalidArgumentException('At least one date boundary must be supplied');
        }
        $output_format = $this->input_format ?? 'jS F Y H:i:s';
        if ($this->min !== null) {
            $this->min_string = $this->min->format($output_format);
        }
        if ($this->max !== null) {
            $this->max_string = $this->max->format($output_format);
        }
    }
    public function is_valid(mixed $value): bool
    {
        $this->type = get_debug_type($value);
        $this->set_value($value);
        if (!is_string($value) && !$value instanceof DateTimeInterface) {
            $this->error(self::ERROR_INVALID_TYPE);
            return false;
        }
        $date = $this->value_to_date($value);
        if ($date === null) {
            $this->error(self::ERROR_INVALID_DATE);
            return false;
        }
        if ($this->min !== null && $this->inclusive_min && $date < $this->min) {
            $this->error(self::ERROR_NOT_GREATER_INCLUSIVE);
            return false;
        }
        if ($this->min !== null && !$this->inclusive_min && $date <= $this->min) {
            $this->error(self::ERROR_NOT_GREATER);
            return false;
        }
        if ($this->max !== null && $this->inclusive_max && $date > $this->max) {
            $this->error(self::ERROR_NOT_LESS_INCLUSIVE);
            return false;
        }
        if ($this->max !== null && !$this->inclusive_max && $date >= $this->max) {
            $this->error(self::ERROR_NOT_LESS);
            return false;
        }
        return true;
    }
    private function value_to_date(string|DateTimeInterface $input): DateTimeInterface|null
    {
        if ($input instanceof DateTimeInterface) {
            return $this->w3c_date_from_string($input->format('Y-m-d\TH:i:s'));
        }
        if ($this->input_format !== null) {
            $date = DateTimeImmutable::create_from_format($this->input_format, $input, new DateTimeZone('UTC'));
            if ($date instanceof DateTimeImmutable) {
                return $date;
            }
        }
        $date = $this->iso_date_from_string($input);
        if ($date !== null) {
            return $date;
        }
        $date = $this->w3c_date_from_string($input);
        if ($date !== null) {
            return $date;
        }
        return null;
    }
    private function date_instance_bound(string|DateTimeInterface|null $date_time): DateTimeInterface|null
    {
        if ($date_time instanceof DateTimeInterface) {
            return $this->w3c_date_from_string($date_time->format('Y-m-d\TH:i:s'));
        }
        if ($date_time === null) {
            return null;
        }
        $date = $this->iso_date_from_string($date_time);
        if ($date !== null) {
            return $date;
        }
        $date = $this->w3c_date_from_string($date_time);
        if ($date !== null) {
            return $date;
        }
        throw new InvalidArgumentException('Min/max date bounds must be either DateTime instances, or a string in one of the formats: ' . '"Y-m-d" for a date or "Y-m-d\TH:i:s" for date time');
    }
    private function iso_date_from_string(string $input): DateTimeImmutable|null
    {
        if (!preg_match('/^\d{4}-[0-1]\d\-[0-3]\d$/', $input)) {
            return null;
        }
        $date = DateTimeImmutable::create_from_format('!Y-m-d', $input, new DateTimeZone('UTC'));
        assert($date !== false);
        return $date;
    }
    private function w3c_date_from_string(string $input): DateTimeImmutable|null
    {
        if (!preg_match('/^\d{4}-[0-1]\d\-[0-3]\dT\d{1,2}:[0-5]\d:[0-5]\d$/', $input)) {
            return null;
        }
        $date = DateTimeImmutable::create_from_format('Y-m-d\TH:i:s', $input, new DateTimeZone('UTC'));
        assert($date !== false);
        return $date;
    }
}