<?php

declare (strict_types=1);
namespace Laminas\Validator;

/**
 * @psalm-import-type ValidatorSpecification from ValidatorInterface
 */
final readonly class Validator_Chain_Factory
{
    public function __construct(private Validator_Plugin_Manager $plugin_manager)
    {
    }
    /** @param array<array-key, ValidatorSpecification|ValidatorInterface> $specification */
    public function from_array(array $specification): Validator_Chain
    {
        $chain = new Validator_Chain($this->plugin_manager);
        foreach ($specification as $spec) {
            if ($spec instanceof Validator_Interface) {
                $chain->attach($spec);
                continue;
            }
            $priority = $spec['priority'] ?? Validator_Chain_Interface::DEFAULT_PRIORITY;
            $break_chain = $spec['break_chain_on_failure'] ?? false;
            $options = $spec['options'] ?? [];
            $chain->attach_by_name($spec['name'], $options, $break_chain, $priority);
        }
        return $chain;
    }
}