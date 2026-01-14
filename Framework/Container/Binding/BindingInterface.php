<?php namespace Kodhe\Framework\Container\Binding;

use Closure;

/**
 * Service Provider Interface
 */
interface BindingInterface
{
    public function register($name, $object);
    public function bind($name, $object);
    public function registerSingleton($name, $object);
    public function make();
}
