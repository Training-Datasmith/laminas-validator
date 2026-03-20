<?php

declare (strict_types=1);
namespace Laminas\Validator;

use function class_exists;
use Laminas\Translator\Translator_Interface;
use Laminas\Validator\Exception\InvalidArgumentException;
/**
 * @psalm-type OptionsArgument = array{
 *     className: class-string,
 *     messages?: array<string, string>,
 *     translator?: TranslatorInterface|null,
 *     translatorTextDomain?: string|null,
 *     translatorEnabled?: bool,
 *     valueObscured?: bool,
 * }
 */
final class Is_Instance_Of extends Abstract_Validator
{
    public const NOT_INSTANCE_OF = 'notInstanceOf';
    /**
     * Validation failure message template definitions
     *
     * @var array<string, string>
     */
    protected array $message_templates = [self::NOT_INSTANCE_OF => "The input is not an instance of '%className%'"];
    /** @var array<string, string|array<string, string>> */
    protected array $message_variables = ['className' => 'className'];
    /** @var class-string */
    protected readonly string $class_name;
    /**
     * Sets validator options
     *
     * @param OptionsArgument $options
     * @throws InvalidArgumentException
     */
    public function __construct(array $options)
    {
        $class_name = $options['className'] ?? null;
        if ($class_name === null || !class_exists($class_name)) {
            throw new InvalidArgumentException('The className option must be a non-empty class-string for an existing class');
        }
        $this->class_name = $class_name;
        unset($options['className']);
        parent::__construct($options);
    }
    /**
     * Returns true if $value is instance of $this->className
     */
    public function is_valid(mixed $value): bool
    {
        if ($value instanceof $this->class_name) {
            return true;
        }
        $this->error(self::NOT_INSTANCE_OF);
        return false;
    }
}