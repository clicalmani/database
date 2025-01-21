<?php
namespace Clicalmani\Database\Factory\Models;

trait StateChange
{
    /**
     * Check if the model is modified since last fetch
     * 
     * @return bool
     */
    public function isDirty(?string $attribute = null) : bool
    {
        $manipulated = $this->getData();

        if ( empty($manipulated) ) return false;
        
        if ( isset($attribute) ) {
            return array_key_exists($attribute, array_merge($manipulated['in'], $manipulated['out']));
        }

        return false;
    }

    /**
     * Check if the model is not modified since last fetch
     * 
     * @param ?string $attribute
     * @return bool
     */
    public function isClean(?string $attribute = null) : bool
    {
        return !$this->isDirty($attribute);
    }

    /**
     * Check if the model is modified since last fetch
     * 
     * @param mixed ...$attribues
     * @return bool
     */
    public function wasChanged(mixed ...$attribues) : bool
    {
        foreach ($attribues as $attribute) {
            if ( $this->isDirty($attribute) ) return true;
        }

        return false;
    }
}