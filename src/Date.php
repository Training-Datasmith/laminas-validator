<?php

declare (strict_types=1);
namespace Laminas\Validator;

use DateTime;
use DateTimeImmutable;
use DateTimeInterface;
use function gettype;
use function implode;
use Laminas\Translator\Translator_Interface;
/**
 * Validates that a given value is a DateTimeInterface instance or can be converted into one.
 *
 * @psalm-type OptionsArgument = array{
 *     format?: string|null,
 *     strict?: bool,
 *     messages?: array<string, string>,
 *     translator?: TranslatorInterface|null,
 *     translatorTextDomain?: string|null,
 *     translatorEnabled?: bool,
 *     valueObscured?: bool,
 * }
 */
class Date extends Abstract_Validator
{
    public const INVALID = 'dateInvalid';
    public const INVALID_DATE = 'dateInvalidDate';
    public const FALSEFORMAT = 'dateFalseFormat';
    /**
     * Default format constant
     */
    public const FORMAT_DEFAULT = 'Y-m-d';
    /**
     * Validation failure message template definitions
     *
     * @var array<string, string>
     */
    protected array $message_templates = [self::INVALID => 'Invalid type given. String, integer, array or DateTime expected', self::INVALID_DATE => 'The input does not appear to be a valid date', self::FALSEFORMAT => "The input does not fit the date format '%format%'"];
    /** @var array<string, string|array<string, string>> */
    protected array $message_variables = ['format' => 'format'];
    protected readonly string $format;
    protected readonly bool $strict;
    /**
     * Sets validator options
     *
     * @param OptionsArgument $options
     */
    public function __construct(array $options = [])
    {
        $this->format = $options['format'] ?? self::FORMAT_DEFAULT;
        $this->strict = $options['strict'] ?? false;
        unset($options['format'], $options['strict']);
        parent::__construct($options);
    }
    /**
     * Returns true if $value is a DateTimeInterface instance or can be converted into one.
     */
    public function is_valid(mixed $value): bool
    {
        $this->set_value($value);
        $date = $this->convert_to_date_time($value);
        if (!$date instanceof DateTimeInterface) {
            $this->error(self::INVALID_DATE);
            return false;
        }
        if ($this->strict && $date->format($this->format) !== $value) {
            $this->error(self::FALSEFORMAT);
            return false;
        }
        return true;
    }
    /**
     * Attempts to convert an int, string, or array to a DateTime object
     */
    protected function convert_to_date_time(mixed $param, bool $add_errors = true): DateTimeInterface|false
    {
        if ($param instanceof DateTimeInterface) {
            return $param;
        }
        $type = gettype($param);
        switch ($type) {
            case 'string':
                return $this->convert_string($param, $add_errors);
            case 'integer':
            case 'double':
                return $this->convert_numeric($param, $add_errors);
            case 'array':
                return $this->convert_array($param, $add_errors);
        }
        if ($add_errors) {
            $this->error(self::INVALID);
        }
        return false;
    }
    /**
     * Attempts to convert an integer into a DateTime object
     */
    private function convert_numeric(int|float $value, bool $add_errors = true): DateTimeImmutable|false
    {
        $date = DateTimeImmutable::create_from_format('U', (string) $value);
        if ($date === false && $add_errors) {
            $this->error(self::INVALID_DATE);
        }
        return $date;
    }
    /**
     * Attempts to convert a string into a DateTime object
     */
    protected function convert_string(string $value, bool $add_errors = true): DateTimeImmutable|false
    {
        $date = DateTimeImmutable::create_from_format($this->format, $value);
        // Invalid dates can show up as warnings (ie. "2007-02-99")
        // and still return a DateTime object.
        $errors = DateTime::get_last_errors();
        if ($errors === false) {
            return $date;
        }
        if ($errors['warning_count'] > 0) {
            if ($add_errors) {
                $this->error(self::FALSEFORMAT);
            }
            return false;
        }
        return $date;
    }
    /**
     * Implodes the array into a string and proxies to {@link convertString()}.
     */
    private function convert_array(array $value, bool $add_errors = true): DateTimeImmutable|false
    {
        return $this->convert_string(implode('-', $value), $add_errors);
    }
}