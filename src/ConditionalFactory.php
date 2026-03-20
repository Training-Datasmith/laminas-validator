<?php

declare (strict_types=1);
namespace Laminas\Validator;

use Laminas\Service_Manager\Factory\Factory_Interface;
use Psr\Container\Container_Interface;
final class Conditional_Factory implements Factory_Interface
{
    /** @inheritDoc */
    public function __invoke(Container_Interface $container, string $requested_name, ?array $options = null): Conditional
    {
        /** @psalm-suppress MixedArgumentTypeCoercion - It's not worth attempting runtime validation of the options shape here */
        return new Conditional($container->get(Validator_Chain_Factory::class), $options ?? []);
    }
}