<?php

namespace Daniesy\DOMinator\Traits;

trait HandlesAttributes
{
    /**
     * Checks if the node has the specified attribute
     */
    public function hasAttribute(string $name): bool
    {
        return array_key_exists($name, $this->attributes);
    }

    /**
     * Gets the value of an attribute if it exists or return false
     */
    public function getAttribute(string $name): string | false
    {
        return $this->hasAttribute($name) ? $this->attributes[$name] : false;
    }

    /**
     * Sets or updates an attribute.
     */
    public function setAttribute(string $name, $value): void {
        $this->attributes[$name] = $value;
    }

    /**
     * Removes an attribute.
     */
    public function removeAttribute(string $name): void {
        unset($this->attributes[$name]);
    }
}