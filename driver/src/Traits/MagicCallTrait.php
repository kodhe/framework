<?php

namespace Kodhe\Framework\Driver\Traits;

/**
 * Trait MagicCallTrait
 *
 * Trait untuk magic method delegation ke parent atau objek lain.
 * Berguna untuk proxy pattern dan delegasi method.
 *
 * @package     Kodhe\Driver\Traits
 * @author      Your Name
 * @version     2.0.0
 * @license     MIT
 */
trait MagicCallTrait
{
    /**
     * Parent object untuk delegasi
     *
     * @var object|null
     */
    protected $parentObject = null;

    /**
     * Set parent object untuk delegasi
     *
     * @param object $parent Parent object
     * @return self
     */
    public function setParent($parent): self
    {
        $this->parentObject = $parent;
        return $this;
    }

    /**
     * Get parent object
     *
     * @return object|null Parent object or null
     */
    public function getParent()
    {
        return $this->parentObject;
    }

    /**
     * Magic getter untuk delegasi ke parent
     *
     * @param string $key Property name
     * @return mixed Property value or null
     */
    public function __get(string $key)
    {
        if ($this->parentObject !== null && property_exists($this->parentObject, $key)) {
            return $this->parentObject->{$key};
        }

        return null;
    }

    /**
     * Magic setter untuk delegasi ke parent
     *
     * @param string $key Property name
     * @param mixed $value Property value
     * @return void
     */
    public function __set(string $key, $value): void
    {
        if ($this->parentObject !== null) {
            $this->parentObject->{$key} = $value;
        }
    }

    /**
     * Magic caller untuk delegasi ke parent
     *
     * @param string $method Method name
     * @param array $args Method arguments
     * @return mixed Method result
     * @throws \BadMethodCallException Jika method tidak ada di parent
     */
    public function __call(string $method, array $args)
    {
        if ($this->parentObject !== null && method_exists($this->parentObject, $method)) {
            return call_user_func_array([$this->parentObject, $method], $args);
        }

        throw new \BadMethodCallException(
            "Method {$method} does not exist"
        );
    }

    /**
     * Check if parent has method
     *
     * @param string $method Method name
     * @return bool True if exists, false if not
     */
    public function parentHasMethod(string $method): bool
    {
        return $this->parentObject !== null && method_exists($this->parentObject, $method);
    }

    /**
     * Check if parent has property
     *
     * @param string $property Property name
     * @return bool True if exists, false if not
     */
    public function parentHasProperty(string $property): bool
    {
        return $this->parentObject !== null && property_exists($this->parentObject, $property);
    }
}
