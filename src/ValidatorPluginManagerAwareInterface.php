<?php

declare (strict_types=1);
namespace Laminas\Validator;

/**
 * @deprecated Since 3.9.0 In world where we should be implementing dependency injection, this interface should not
 *             be required. It's presence indicates ongoing use of initializers which are not performant and lead to
 *             objects being constructed in an invalid state. This interface will be removed in 4.0.0
 */
interface Validator_Plugin_Manager_Aware_Interface
{
    /**
     * Set validator plugin manager
     *
     * @return void
     */
    public function set_validator_plugin_manager(Validator_Plugin_Manager $plugin_manager);
    /**
     * Get validator plugin manager
     *
     * @return ValidatorPluginManager
     */
    public function get_validator_plugin_manager();
}