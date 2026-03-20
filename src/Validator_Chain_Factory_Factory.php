<?php

declare (strict_types=1);
namespace Laminas\Validator;

use Psr\Container\Container_Interface;
final class Validator_Chain_Factory_Factory
{
    public function __invoke(Container_Interface $container): Validator_Chain_Factory
    {
        return new Validator_Chain_Factory($container->get(Validator_Plugin_Manager::class));
    }
}