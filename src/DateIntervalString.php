<?php

declare (strict_types=1);
namespace Laminas\Validator;

use DateInterval;
use function get_debug_type;
use function is_string;
use Throwable;
final class Date_Interval_String extends Abstract_Validator
{
    public const ERR_NOT_STRING = 'errorNotString';
    public const ERR_INVALID = 'errorInvalid';
    /** @var array<string, string> */
    protected array $message_templates = [self::ERR_NOT_STRING => 'Expected a string value but %type% provided', self::ERR_INVALID => 'Invalid date interval specification "%value%'];
    protected ?string $type = null;
    /** @var array<string, string|array<string, string>> */
    protected array $message_variables = ['type' => 'type'];
    public function is_valid(mixed $value): bool
    {
        $this->set_value($value);
        $this->type = get_debug_type($value);
        if (!is_string($value)) {
            $this->error(self::ERR_NOT_STRING);
            return false;
        }
        try {
            new DateInterval($value);
        } catch (Throwable) {
            $this->error(self::ERR_INVALID);
            return false;
        }
        return true;
    }
}