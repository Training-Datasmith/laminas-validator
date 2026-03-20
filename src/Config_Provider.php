<?php

declare (strict_types=1);
namespace Laminas\Validator;

use Laminas\Service_Manager\Service_Manager;
/** @psalm-import-type ServiceManagerConfiguration from ServiceManager */
final class Config_Provider
{
    /**
     * Return configuration for this component.
     *
     * @return array{dependencies: ServiceManagerConfiguration}
     */
    public function __invoke(): array
    {
        return ['dependencies' => $this->get_dependency_config()];
    }
    /**
     * Return dependency mappings for this component.
     *
     * @return ServiceManagerConfiguration
     */
    public function get_dependency_config(): array
    {
        return ['aliases' => ['ValidatorManager' => Validator_Plugin_Manager::class, Validator_Chain_Interface::class => Validator_Chain::class], 'factories' => [Validator_Chain_Factory::class => Validator_Chain_Factory_Factory::class, Validator_Plugin_Manager::class => Validator_Plugin_Manager_Factory::class]];
    }
}