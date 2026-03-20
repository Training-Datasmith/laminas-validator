<?php

declare (strict_types=1);
namespace Laminas\Validator\Exception;

use function get_debug_type;
use InvalidArgumentException;
use function sprintf;
final class Invalid_Specification_Array_Exception extends InvalidArgumentException implements Exception_Interface
{
    public static function because_items_must_be_arrays_or_validators(mixed $spec): self
    {
        return new self(sprintf('Each item in a specification array must be either an array or a validator instance. Received %s', get_debug_type($spec)));
    }
    public static function because_the_name_is_a_required_key(mixed $name): self
    {
        return new self(sprintf('The `name` key must be defined and should be a string representing the FQCN of a validator or an ' . 'alias. Received %s', get_debug_type($name)));
    }
    public static function because_options_must_be_an_array(mixed $options): self
    {
        return new self(sprintf('When given, the `options` key must be an array. Received %s', get_debug_type($options)));
    }
    public static function because_break_chain_must_be_boolean(mixed $break_chain): self
    {
        return new self(sprintf('When given, the `break_chain_on_failure` key must be boolean. Received %s', get_debug_type($break_chain)));
    }
    public static function because_priority_must_be_an_integer(mixed $priority): self
    {
        return new self(sprintf('When given, the `priority` key must be an integer. Received %s', get_debug_type($priority)));
    }
}