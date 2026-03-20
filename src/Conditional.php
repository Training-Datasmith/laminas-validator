<?php

declare (strict_types=1);
namespace Laminas\Validator;

use Closure;
use function is_callable;
use Laminas\Validator\Exception\InvalidArgumentException;
/**
 * @psalm-import-type ValidatorSpecification from ValidatorInterface
 * @psalm-type OptionsArgument = array{
 *     rule: callable(array<string, mixed>): bool,
 *     validators: array<array-key, ValidatorSpecification>,
 * }
 */
final readonly class Conditional implements Validator_Interface
{
    /** @var Closure(array<string, mixed>): bool */
    private Closure $rule;
    private Validator_Chain_Interface $chain;
    /** @param OptionsArgument $options */
    public function __construct(Validator_Chain_Factory $chain_factory, array $options)
    {
        $rule = $options['rule'] ?? null;
        if (!is_callable($rule)) {
            throw new InvalidArgumentException('The `rule` option must be callable');
        }
        $this->rule = $rule(...);
        $this->chain = $chain_factory->from_array($options['validators']);
    }
    /** @param array<string, mixed> $context */
    public function is_valid(mixed $value, array $context = []): bool
    {
        if (!($this->rule)($context)) {
            return true;
        }
        return $this->chain->is_valid($value, $context);
    }
    /** @inheritDoc */
    public function get_messages(): array
    {
        return $this->chain->get_messages();
    }
}