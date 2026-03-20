<?php

declare (strict_types=1);
namespace Laminas\Validator;

use function get_debug_type;
use function is_array;
final class Is_Array extends Abstract_Validator
{
    public const NOT_ARRAY = 'NotArray';
    /** @var array<string, string> */
    protected array $message_templates = [self::NOT_ARRAY => 'Expected an array value but %type% provided'];
    /** @var array<string, string|array<string, string>> */
    protected array $message_variables = ['type' => 'type'];
    protected ?string $type = null;
    public function is_valid(mixed $value): bool
    {
        if (is_array($value)) {
            return true;
        }
        $this->type = get_debug_type($value);
        $this->error(self::NOT_ARRAY);
        return false;
    }
}