<?php

declare (strict_types=1);
namespace Laminas\Validator;

use function assert;
use Backed_Enum;
use function get_debug_type;
use function is_a;
use function is_int;
use function is_scalar;
use function is_string;
use Laminas\Translator\Translator_Interface;
use Laminas\Validator\Exception\InvalidArgumentException;
use Reflection_Enum;
use ReflectionNamedType;
use function sprintf;
/**
 * @psalm-type OptionsArgument = array{
 *     enum: class-string<BackedEnum>,
 *     messages?: array<string, string>,
 *     translator?: TranslatorInterface|null,
 *     translatorTextDomain?: string|null,
 *     translatorEnabled?: bool,
 *     valueObscured?: bool,
 * }
 */
final class Backed_Enum_Value extends Abstract_Validator
{
    public const ERR_INVALID_TYPE = 'errInvalidType';
    public const ERR_INVALID_VALUE = 'errInvalidValue';
    /** @var array<string, string> */
    protected array $message_templates = [self::ERR_INVALID_TYPE => 'Expected a string or numeric value but received %type%', self::ERR_INVALID_VALUE => '"%value%" is not a valid enum case'];
    /** @var class-string<BackedEnum> */
    private readonly string $enum;
    private readonly bool $is_string_backed;
    protected ?string $type = null;
    /** @var array<string, string|array<string, string>> */
    protected array $message_variables = ['type' => 'type'];
    /** @param OptionsArgument $options */
    public function __construct(array $options)
    {
        $enum = $options['enum'];
        if (!is_a($enum, Backed_Enum::class, true)) {
            throw new InvalidArgumentException(sprintf('Expected the `enum` option to be a backed enum class-string but "%s" received', $enum));
        }
        $this->enum = $enum;
        $reflection = new Reflection_Enum($this->enum);
        $reflection_type = $reflection->get_backing_type();
        assert($reflection_type instanceof ReflectionNamedType);
        $this->is_string_backed = $reflection_type->get_name() === 'string';
        parent::__construct($options);
    }
    public function is_valid(mixed $value): bool
    {
        $this->set_value(is_scalar($value) ? (string) $value : get_debug_type($value));
        $this->type = get_debug_type($value);
        if (!is_string($value) && !is_int($value)) {
            $this->error(self::ERR_INVALID_TYPE);
            return false;
        }
        if ($this->is_string_backed && !is_string($value)) {
            $this->error(self::ERR_INVALID_TYPE);
            return false;
        }
        if (!$this->is_string_backed && (string) (int) $value !== (string) $value) {
            $this->error(self::ERR_INVALID_TYPE);
            return false;
        }
        $value = $this->is_string_backed ? $value : (int) $value;
        $enum = $this->enum::try_from($value);
        if ($enum === null) {
            $this->error(self::ERR_INVALID_VALUE);
            return false;
        }
        return true;
    }
}