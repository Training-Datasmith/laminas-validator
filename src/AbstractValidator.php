<?php

declare (strict_types=1);
namespace Laminas\Validator;

use function array_key_exists;
use function array_keys;
use function array_unique;
use function assert;
use function implode;
use function is_array;
use function is_bool;
use function is_object;
use function is_string;
use function key;
use Laminas\Translator\Translator_Interface;
use Laminas\Validator\Exception\InvalidArgumentException;
use function method_exists;
use function property_exists;
use const SORT_REGULAR;
use function sprintf;
use function str_repeat;
use function str_replace;
use function strlen;
use function substr;
use function var_export;
/**
 * @psalm-type AbstractOptions = array{
 *     messages?: array<string, string>,
 *     translator?: TranslatorInterface|null,
 *     translatorTextDomain?: string|null,
 *     translatorEnabled?: bool,
 *     valueObscured?: bool,
 *     ...<string, mixed>
 * }
 */
abstract class Abstract_Validator implements Translator\Translator_Aware_Interface, Validator_Interface
{
    /**
     * The value to be validated
     *
     * phpcs:disable WebimpressCodingStandard.Classes.NoNullValues
     */
    protected mixed $value = null;
    /**
     * Default translation object for all validate objects
     */
    private static ?Translator_Interface $default_translator = null;
    /**
     * Default text domain to be used with translator
     */
    private static string $default_translator_text_domain = 'default';
    /**
     * Limits the maximum returned length of an error message
     */
    private static int $message_length = -1;
    /**
     * An array that defines the default translations (in english) of the validators error messages
     *
     * @var array<string, string>
     */
    protected array $message_templates = [];
    /**
     * An array that defines substitutions that will be interpolated into error messages.
     *
     * Keys are the placeholder name and the values must be a string that references protected or public properties of
     * the validator, for example ['myProperty' => 'myProperty'] would replace "%myProperty%" in an error message with
     * the value of "$this->myProperty".
     *
     * You can also specify a replacement as an array such as ['myProperty' => ['props' => 'this-one']]. In this case,
     * the placeholder '%myProperty%' would be replaced with the value "$this->props['this-one']".
     *
     * @var array<string, string|array<string, string>>
     */
    protected array $message_variables = [];
    /** Flag indicating whether value should be obfuscated in error messages */
    private bool $value_obscured = false;
    /** Whether translation should be enabled or not */
    private bool $translator_enabled = true;
    /** The text domain for translations */
    private string $translator_text_domain = 'default';
    /** A custom translator, the default translator, or null */
    private Translator_Interface|null $translator = null;
    /**
     * Error messages that have occurred during the last validation
     *
     * @var array<string, string>
     */
    protected array $error_messages = [];
    /**
     * Abstract constructor for all validators
     *
     * Custom validators should call `parent::__construct($options)` after processing validator specific options in
     * order to ensure that:
     * - User supplied, custom errors messages override the default error messages
     * - The configured translator is correctly set and/or enabled
     *
     * @param AbstractOptions $options
     */
    public function __construct(array $options = [])
    {
        $value_obscured = $options['valueObscured'] ?? false;
        $translator_enabled = $options['translatorEnabled'] ?? true;
        $translator_text_domain = $options['translatorTextDomain'] ?? self::$default_translator_text_domain;
        $translator = $options['translator'] ?? self::$default_translator;
        $messages = $options['messages'] ?? [];
        assert(is_bool($translator_enabled));
        assert(is_string($translator_text_domain));
        assert($translator instanceof Translator_Interface || $translator === null);
        assert(is_array($messages));
        $this->value_obscured = $value_obscured;
        $this->translator_enabled = $translator_enabled;
        $this->translator_text_domain = $translator_text_domain;
        $this->translator = $translator;
        /** @psalm-var array<string, string> $messages Psalm cannot infer this from the declared type */
        $this->override_messages_with($messages);
    }
    /**
     * Returns array of validation failure messages
     *
     * @return array<string, string>
     */
    public function get_messages(): array
    {
        return array_unique($this->error_messages, SORT_REGULAR);
    }
    /**
     * Invoke as command
     */
    public function __invoke(mixed $value): bool
    {
        return $this->is_valid($value);
    }
    /**
     * Sets the validation failure message template for a particular key
     *
     * Omitting the `$messageKey` parameter will cause _all_ error messages to have the same value.
     *
     * @throws InvalidArgumentException If the supplied $messageKey does not correspond to a known error message key.
     */
    public function set_message(string $message_string, ?string $message_key = null): void
    {
        if ($message_key === null) {
            $keys = array_keys($this->message_templates);
            foreach ($keys as $key) {
                $this->set_message($message_string, $key);
            }
            return;
        }
        if (!isset($this->message_templates[$message_key])) {
            throw new InvalidArgumentException("No message template exists for key '{$message_key}'");
        }
        $this->message_templates[$message_key] = $message_string;
    }
    /**
     * Constructs and returns a validation failure message with the given message key and value.
     *
     * Returns null if and only if $messageKey does not correspond to an existing template.
     *
     * If a translator is available and a translation exists for $messageKey,
     * the translation will be used.
     */
    private function create_message(string $message_key, mixed $value): ?string
    {
        if (!isset($this->message_templates[$message_key])) {
            return null;
        }
        $message = $this->translate_message($this->message_templates[$message_key]);
        $message = $this->substitute_placeholder('value', $value, $message, $this->value_obscured);
        foreach ($this->message_variables as $id => $property) {
            $message = $this->substitute_placeholder($id, $this->property_value($property), $message, false);
        }
        $length = self::$message_length;
        if ($length > -1 && strlen($message) > $length) {
            return substr($message, 0, $length - 3) . '...';
        }
        return $message;
    }
    /** @param string|array<string, string> $prop */
    private function property_value(string|array $prop): mixed
    {
        if (is_string($prop)) {
            assert(property_exists($this, $prop));
            /** @psalm-var mixed $value */
            return $this->{$prop};
        }
        $name = key($prop);
        assert(is_string($name));
        assert(property_exists($this, $name));
        $key = $prop[$name];
        /** @psalm-var mixed $value */
        $value = $this->{$name};
        assert(is_array($value));
        assert(array_key_exists($key, $value));
        return $value[$key];
    }
    private function substitute_placeholder(string $id, mixed $value, string $message, bool $obscure): string
    {
        $search = "%{$id}%";
        $value = $this->stringify_value($value);
        if ($obscure) {
            $value = str_repeat('*', strlen($value));
        }
        return str_replace($search, $value, $message);
    }
    private function stringify_value(mixed $value): string
    {
        if (is_object($value)) {
            return method_exists($value, '__toString') ? (string) $value : $value::class . ' object';
        }
        if (is_array($value)) {
            return var_export($value, true);
        }
        return (string) $value;
    }
    protected function error(string $message_key, mixed $value = null): void
    {
        if ($value === null) {
            /** @psalm-var mixed $value */
            $value = $this->value;
        }
        $message = $this->create_message($message_key, $value);
        if (!is_string($message)) {
            return;
        }
        $this->error_messages[$message_key] = $message;
    }
    /**
     * Returns the validation value
     */
    protected function get_value(): mixed
    {
        return $this->value;
    }
    /**
     * Set the validated value
     *
     * Sets the validated value so that it can be interpolated in error messages and clears any previous validation
     * failure messages.
     */
    protected function set_value(mixed $value): void
    {
        $this->value = $value;
        $this->error_messages = [];
    }
    /**
     * Set the translator for this instance
     */
    public function set_translator(?Translator_Interface $translator = null, ?string $text_domain = null): void
    {
        $this->translator = $translator;
        if ($text_domain !== null) {
            $this->translator_text_domain = $text_domain;
        }
    }
    /**
     * Return the translator for this instance
     */
    public function get_translator(): ?Translator_Interface
    {
        return $this->translator;
    }
    /**
     * Set the default, static translator for all validators
     */
    public static function set_default_translator(?Translator_Interface $translator = null, ?string $text_domain = null): void
    {
        self::$default_translator = $translator;
        if (null !== $text_domain) {
            self::set_default_translator_text_domain($text_domain);
        }
    }
    /**
     * Set default translation text domain for all validator instances
     */
    public static function set_default_translator_text_domain(string $text_domain = 'default'): void
    {
        self::$default_translator_text_domain = $text_domain;
    }
    /**
     * Sets the maximum allowed message length for all validator instances
     */
    public static function set_message_length(int $length = -1): void
    {
        self::$message_length = $length;
    }
    /**
     * Translate a validation message
     */
    private function translate_message(string $message): string
    {
        if (!$this->translator_enabled || !$this->translator) {
            return $message;
        }
        return $this->translator->translate($message, $this->translator_text_domain);
    }
    /**
     * Overrides message templates for this instance
     *
     * @param array<string, string> $customMessages
     */
    private function override_messages_with(array $custom_messages): void
    {
        foreach ($custom_messages as $key => $message) {
            if (!array_key_exists($key, $this->message_templates)) {
                throw new InvalidArgumentException(sprintf('The error message key "%s" does not exist. Possible keys are "%s"', $key, implode(', ', array_keys($this->message_templates))));
            }
            $this->message_templates[$key] = $message;
        }
    }
}