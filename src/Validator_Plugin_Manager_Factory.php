<?php

declare (strict_types=1);
namespace Laminas\Validator;

use function assert;
use function is_array;
use Laminas\Service_Manager\Service_Manager;
use Psr\Container\Container_Interface;
/** @psalm-import-type ServiceManagerConfiguration from ServiceManager */
final class Validator_Plugin_Manager_Factory
{
    public function __invoke(Container_Interface $container): Validator_Plugin_Manager
    {
        $config = $container->has('config') ? $container->get('config') : [];
        assert(is_array($config));
        /** @psalm-var ServiceManagerConfiguration $validators */
        $validators = isset($config['validators']) && is_array($config['validators']) ? $config['validators'] : [];
        return new Validator_Plugin_Manager($container, $validators);
    }
}