<?php

declare (strict_types=1);
namespace Laminas\Validator;

use Laminas\Service_Manager\Factory\Factory_Interface;
use Psr\Container\Container_Interface;
/**
 * This factory enables chain creation via the plugin manager.
 *
 * Example:
 *  $chain = $pluginManager->get(ValidatorChain::class); // Empty chain
 *  $chain = $pluginManager->build(ValidatorChain::class, $options); // Configured Chain
 *
 * @psalm-import-type ValidatorSpecification from ValidatorInterface
 * @psalm-internal Laminas\Validator
 * @psalm-internal LaminasTest\Validator
 */
final class Validator_Chain_Invokable_Factory implements Factory_Interface
{
    /**
     * @param array<array-key, ValidatorSpecification|ValidatorInterface>|null $options
     * @psalm-suppress MoreSpecificImplementedParamType $options is more specific to prevent psalm from requiring
     *                 runtime validation of the validator chain options payload.
     */
    public function __invoke(Container_Interface $container, string $requested_name, ?array $options = null): Validator_Chain
    {
        $options ??= [];
        $factory = $container->get(Validator_Chain_Factory::class);
        return $factory->from_array($options);
    }
}