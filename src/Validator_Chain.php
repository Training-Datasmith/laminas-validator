<?php

declare (strict_types=1);
namespace Laminas\Validator;

use function array_replace;
use function assert;
use function count;
use Countable;
use function is_array;
use function is_bool;
use function is_int;
use function is_string;
use IteratorAggregate;
use Laminas\Service_Manager\Service_Manager;
use Laminas\Stdlib\Priority_Queue;
use Laminas\Validator\Exception\Invalid_Specification_Array_Exception;
use function rsort;
use const SORT_NUMERIC;
use Traversable;
/**
 * @psalm-type QueueElement = array{instance: ValidatorInterface, breakChainOnFailure: bool}
 * @implements IteratorAggregate<array-key, QueueElement>
 * @psalm-type ValidatorSpecification = array{
 *     name: non-empty-string|class-string<ValidatorInterface>,
 *     options?: array<string, mixed>,
 *     break_chain_on_failure?: bool,
 *     priority?: int,
 * }
 * @psalm-type ValidatorChainSpecification = array<array-key, ValidatorSpecification|ValidatorInterface>
 */
final class Validator_Chain implements Countable, IteratorAggregate, Validator_Chain_Interface
{
    /**
     * Default priority at which validators are added
     *
     * @deprecated Use ValidatorChainInterface::DEFAULT_PRIORITY instead.
     */
    public const DEFAULT_PRIORITY = Validator_Chain_Interface::DEFAULT_PRIORITY;
    /**
     * Validator chain
     *
     * @var PriorityQueue<QueueElement, int>
     */
    private Priority_Queue $validators;
    /**
     * Array of validation failure messages
     *
     * @var array<string, string>
     */
    private array $messages = [];
    /**
     * Initialize validator chain
     */
    public function __construct(private Validator_Plugin_Manager|null $plugin_manager = null)
    {
        /** @var PriorityQueue<QueueElement, int> $queue */
        $queue = new Priority_Queue();
        $this->validators = $queue;
    }
    /**
     * Return the count of attached validators
     */
    public function count(): int
    {
        return count($this->validators);
    }
    /**
     * Retrieve the Validator Plugin Manager used by this instance
     *
     * If you need an instance of the plugin manager, you should retrieve it from your DI container. This method is
     * only for internal use and is kept for compatibility with laminas-inputfilter.
     *
     * It is not subject to BC because it is marked as internal and the method may be removed in a minor release.
     *
     * @psalm-internal \Laminas
     * @psalm-internal \LaminasTest
     */
    public function get_plugin_manager(): Validator_Plugin_Manager
    {
        if ($this->plugin_manager === null) {
            $this->plugin_manager = new Validator_Plugin_Manager(new Service_Manager());
        }
        return $this->plugin_manager;
    }
    /**
     * Set plugin manager instance
     *
     * This method is retained for BC with laminas-inputfilter. It is internal and not subject to BC guarantees.
     * It may be removed in a minor release.
     *
     * @psalm-internal \Laminas
     * @psalm-internal \LaminasTest
     */
    public function set_plugin_manager(Validator_Plugin_Manager $plugins): void
    {
        $this->plugin_manager = $plugins;
    }
    /**
     * Retrieve a validator by name
     *
     * This method is retained for BC with laminas-inputfilter. It is internal and not subject to BC guarantees.
     * It may be removed in a minor release.
     *
     * @psalm-internal \Laminas
     * @psalm-internal \LaminasTest
     * @param string|class-string<T> $name Name of validator to return
     * @param array<string, mixed> $options Options to pass to validator constructor
     *                                        (if not already instantiated)
     * @template T of ValidatorInterface
     * @return ($name is class-string<T> ? T : ValidatorInterface)
     */
    public function plugin(string $name, array $options = []): Validator_Interface
    {
        $plugin = $options === [] ? $this->get_plugin_manager()->get($name) : $this->get_plugin_manager()->build($name, $options);
        assert($plugin instanceof Validator_Interface);
        return $plugin;
    }
    /**
     * Attach a validator to the end of the chain
     * If $breakChainOnFailure is true, then if the validator fails, the next validator in the chain,
     * if one exists, will not be executed.
     *
     * @throws Exception\InvalidArgumentException
     */
    public function attach(Validator_Interface $validator, bool $break_chain_on_failure = false, int $priority = Validator_Chain_Interface::DEFAULT_PRIORITY): void
    {
        $this->validators->insert(['instance' => $validator, 'breakChainOnFailure' => $break_chain_on_failure], $priority);
    }
    /**
     * Adds a validator to the beginning of the chain
     *
     * If $breakChainOnFailure is true, then if the validator fails, the next validator in the chain,
     * if one exists, will not be executed.
     */
    public function prepend_validator(Validator_Interface $validator, bool $break_chain_on_failure = false): void
    {
        $priority = Validator_Chain_Interface::DEFAULT_PRIORITY;
        if (!$this->validators->is_empty()) {
            $extracted_nodes = $this->validators->to_array(Priority_Queue::EXTR_PRIORITY);
            rsort($extracted_nodes, SORT_NUMERIC);
            $priority = $extracted_nodes[0] + 1;
        }
        $this->validators->insert(['instance' => $validator, 'breakChainOnFailure' => $break_chain_on_failure], $priority);
    }
    /**
     * Use the plugin manager to add a validator by name
     *
     * @param string|class-string<ValidatorInterface> $name
     * @param array<string, mixed> $options
     */
    public function attach_by_name(string $name, array $options = [], bool $break_chain_on_failure = false, int $priority = Validator_Chain_Interface::DEFAULT_PRIORITY): void
    {
        $bc = null;
        foreach (['break_chain_on_failure', 'breakchainonfailure'] as $key) {
            /** @psalm-var mixed $value */
            $value = $options[$key] ?? null;
            if (is_bool($value)) {
                $bc = $value;
            }
        }
        $bc ??= $break_chain_on_failure;
        $this->attach($this->plugin($name, $options), $bc, $priority);
    }
    /**
     * Use the plugin manager to prepend a validator by name
     *
     * @param string|class-string<ValidatorInterface> $name
     * @param array<string, mixed> $options
     */
    public function prepend_by_name(string $name, array $options = [], bool $break_chain_on_failure = false): void
    {
        $this->prepend_validator($this->plugin($name, $options), $break_chain_on_failure);
    }
    /**
     * Returns true if and only if $value passes all validations in the chain
     *
     * Validators are run in the order in which they were added to the chain (FIFO).
     *
     * @inheritDoc
     */
    public function is_valid(mixed $value, ?array $context = null): bool
    {
        $this->messages = [];
        $result = true;
        foreach ($this as $element) {
            $validator = $element['instance'];
            assert($validator instanceof Validator_Interface);
            if ($validator->is_valid($value, $context)) {
                continue;
            }
            $result = false;
            $this->messages = array_replace($this->messages, $validator->get_messages());
            if ($element['breakChainOnFailure']) {
                break;
            }
        }
        return $result;
    }
    /**
     * Merge the validator chain with the one given in parameter
     */
    public function merge(Validator_Chain $validator_chain): void
    {
        foreach ($validator_chain->validators->to_array(Priority_Queue::EXTR_BOTH) as $item) {
            $this->attach($item['data']['instance'], $item['data']['breakChainOnFailure'], $item['priority']);
        }
    }
    /**
     * Returns array of validation failure messages
     *
     * @return array<string, string>
     */
    public function get_messages(): array
    {
        return $this->messages;
    }
    /**
     * Get all the validators
     *
     * @return list<QueueElement>
     */
    public function get_validators(): array
    {
        return $this->validators->to_array(Priority_Queue::EXTR_DATA);
    }
    /**
     * Invoke chain as command
     */
    public function __invoke(mixed $value): bool
    {
        return $this->is_valid($value);
    }
    /**
     * Deep clone handling
     */
    public function __clone()
    {
        $this->validators = clone $this->validators;
    }
    /**
     * Prepare validator chain for serialization
     *
     * Plugin manager (property 'plugins') cannot
     * be serialized. On wakeup the property remains unset
     * and next invocation to getPluginManager() sets
     * the default plugin manager instance (ValidatorPluginManager).
     *
     * @deprecated Serializing the validator chain is deprecated and will be removed in version 4.0
     *
     * @return list<string>
     */
    public function __sleep(): array
    {
        return ['validators', 'messages'];
    }
    /**
     * @deprecated Serializing the validator chain is deprecated and will be removed in version 4.0
     *
     * @psalm-internal Laminas
     * @psalm-internal LaminasTest
     */
    public function __serialize(): array
    {
        return ['validators' => $this->validators, 'messages' => $this->messages];
    }
    /** @return Traversable<array-key, QueueElement> */
    public function getIterator(): Traversable
    {
        return clone $this->validators;
    }
    /**
     * @param array<array-key, mixed> $spec
     * @psalm-assert ValidatorChainSpecification $spec
     * @throws InvalidSpecificationArrayException If the specification is invalid.
     */
    public static function validate_specification(array $spec): void
    {
        /** @psalm-var mixed $item */
        foreach ($spec as $item) {
            self::validate_validator_spec($item);
        }
    }
    private static function validate_validator_spec(mixed $spec): void
    {
        if ($spec instanceof Validator_Interface) {
            return;
        }
        if (!is_array($spec)) {
            throw Invalid_Specification_Array_Exception::because_items_must_be_arrays_or_validators($spec);
        }
        /** @psalm-var mixed $name */
        $name = $spec['name'] ?? null;
        if (!is_string($name) || $name === '') {
            throw Invalid_Specification_Array_Exception::because_the_name_is_a_required_key($name);
        }
        /** @psalm-var mixed $options */
        $options = $spec['options'] ?? null;
        if ($options !== null && !is_array($options)) {
            throw Invalid_Specification_Array_Exception::because_options_must_be_an_array($options);
        }
        /** @psalm-var mixed $options */
        $break_chain = $spec['break_chain_on_failure'] ?? null;
        if ($break_chain !== null && !is_bool($break_chain)) {
            throw Invalid_Specification_Array_Exception::because_break_chain_must_be_boolean($break_chain);
        }
        /** @psalm-var mixed $priority */
        $priority = $spec['priority'] ?? null;
        if ($priority !== null && !is_int($priority)) {
            throw Invalid_Specification_Array_Exception::because_priority_must_be_an_integer($priority);
        }
    }
}