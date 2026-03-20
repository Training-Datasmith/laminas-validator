<?php

declare (strict_types=1);
namespace Laminas\Validator;

use function explode;
use function implode;
use function is_array;
use function is_string;
use Laminas\Service_Manager\Service_Manager;
use Laminas\Translator\Translator_Interface;
use Laminas\Validator\Exception\RuntimeException;
use function sprintf;
/**
 * @psalm-type OptionsArgument = array{
 *     valueDelimiter?: non-empty-string,
 *     validatorPluginManager?: ValidatorPluginManager|null,
 *     validator?: ValidatorInterface|class-string<ValidatorInterface>|ValidatorSpecification,
 *     breakOnFirstFailure?: bool,
 *     messages?: array<string, string>,
 *     translator?: TranslatorInterface|null,
 *     translatorTextDomain?: string|null,
 *     translatorEnabled?: bool,
 *     valueObscured?: bool,
 * }
 * @psalm-import-type ValidatorSpecification from ValidatorInterface
 */
final class Explode extends Abstract_Validator
{
    public const INVALID = 'explodeInvalid';
    public const INVALID_ITEM = 'invalidItem';
    /** @var array<string, string> */
    protected array $message_templates = [self::INVALID => 'Invalid type given, string expected', self::INVALID_ITEM => '%count% items were invalid: %error%'];
    /** @var non-empty-string */
    private readonly string $value_delimiter;
    private Validator_Plugin_Manager|null $plugin_manager;
    private readonly Validator_Interface $validator;
    private readonly bool $break_on_first_failure;
    protected int $count = 0;
    protected ?string $error = null;
    /** @var array<string, string|array<string, string>> */
    protected array $message_variables = ['count' => 'count', 'error' => 'error'];
    /** @param OptionsArgument $options */
    public function __construct(array $options = [])
    {
        $this->value_delimiter = $options['valueDelimiter'] ?? ',';
        $plugins = $options['validatorPluginManager'] ?? null;
        $this->plugin_manager = $plugins instanceof Validator_Plugin_Manager ? $plugins : null;
        $this->validator = $this->resolve_validator($options['validator'] ?? null);
        $this->break_on_first_failure = $options['breakOnFirstFailure'] ?? false;
        parent::__construct($options);
    }
    private function get_plugin_manager(): Validator_Plugin_Manager
    {
        if (!$this->plugin_manager instanceof Validator_Plugin_Manager) {
            $this->plugin_manager = new Validator_Plugin_Manager(new Service_Manager());
        }
        return $this->plugin_manager;
    }
    /**
     * Sets the Validator for validating each value
     *
     * @param ValidatorInterface|string|ValidatorSpecification $validator
     * @throws RuntimeException
     */
    private function resolve_validator(Validator_Interface|string|array|null $validator): Validator_Interface
    {
        if ($validator instanceof Validator_Interface) {
            return $validator;
        }
        if (is_array($validator)) {
            if (!isset($validator['name'])) {
                throw new RuntimeException('Invalid validator specification provided; does not include "name" key');
            }
            $name = $validator['name'];
            $options = $validator['options'] ?? [];
            return $this->get_plugin_manager()->build($name, $options);
        }
        if (is_string($validator)) {
            return $this->get_plugin_manager()->get($validator);
        }
        throw new RuntimeException(sprintf('%s expects a validator to be set; none given', self::class));
    }
    /**
     * Defined by Laminas\Validator\ValidatorInterface
     *
     * Returns true if all values validate true
     *
     * @param array<string, mixed> $context
     * @throws RuntimeException
     */
    public function is_valid(mixed $value, ?array $context = null): bool
    {
        if (!is_string($value)) {
            $this->error(self::INVALID);
            return false;
        }
        $this->set_value($value);
        $values = explode($this->value_delimiter, $value);
        $this->count = 0;
        foreach ($values as $value) {
            if ($this->validator->is_valid($value, $context)) {
                continue;
            }
            $this->count++;
            if ($this->break_on_first_failure) {
                break;
            }
        }
        if ($this->count > 0) {
            $this->error = implode(', ', $this->validator->get_messages());
            $this->error(self::INVALID_ITEM);
            return false;
        }
        return true;
    }
}