<?php
namespace Clicalmani\Database\Factory\Models;

trait StateChange
{
    public function isDirty(?string $attribute = null) : bool
    {
        $manipulated = $this->getData();

        if ( empty($manipulated) ) return false;
        
        if ( isset($attribute) ) {
            return array_key_exists($attribute, array_merge($manipulated['in'], $manipulated['out']));
        }

        return false;
    }

    public function isClean(?string $attribute = null) : bool
    {
        return !$this->isDirty($attribute);
    }

    public function wasChanged(mixed ...$attribues) : bool
    {
        foreach ($attribues as $attribute) {
            if ( $this->isDirty($attribute) ) return true;
        }

        return false;
    }
}