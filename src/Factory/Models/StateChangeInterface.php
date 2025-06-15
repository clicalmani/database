<?php
namespace Clicalmani\Database\Factory\Models;

interface StateChangeInterface
{
    /**
     * Check if the model is modified since last fetch
     * 
     * @return bool
     */
    public function isDirty(?string $attribute = null) : bool;

    /**
     * Check if the model is not modified since last fetch
     * 
     * @param ?string $attribute
     * @return bool
     */
    public function isClean(?string $attribute = null) : bool;

    /**
     * Check if the model is modified since last fetch
     * 
     * @param mixed ...$attribues
     * @return bool
     */
    public function wasChanged(mixed ...$attribues) : bool;
}