<?php
namespace Clicalmani\Database\Factory\Models;

trait RelationShips
{
    /**
     * The current model inherit a foreign key
     * We should match the model key value to obtain its parent.
     * 
     * @param string $class Parent model
     * @param string $foreign_key [Optional] Table foreign key
     * @param string $parent_key [Optional] original key
     * @return mixed
     */
    protected function belongsTo(string $class, string|null $foreign_key = null, string|null $original_key = null) : mixed
    {
        return ( new $class )->__join($this, $foreign_key, $original_key)
                    ->whereAnd($this->getKeySQLCondition(true))
                    ->fetch()
                    ->first();
    }

    /**
     * One an one relationship: the current model inherit a foreign key
     * 
     * @param string $class Parent model
     * @param string $foreign_key [Optional] Table foreign key
     * @param string $parent_key [Optional] original key
     * @return mixed
     */
    protected function hasOne(string $class, string|null $foreign_key = null, string|null $original_key = null) : mixed
    {
        if ( $this->isEmpty() ) return null;
        
        return $this->__join($class, $foreign_key, $original_key)
                    ->fetch($class)
                    ->first();
    }

    /**
     * One to many relationship
     * 
     * @param string $class Child model
     * @param string $foreign_key [Optional] Table foreign key
     * @param string $original_key [Optional] Original key
     * @return \Clicalmani\Foundation\Collection\Collection
     */
    protected function hasMany(string $class, ?string $foreign_key = null, ?string $original_key = null) : \Clicalmani\Foundation\Collection\Collection
    {
        if ( $this->isEmpty() ) return collection();
        
        return $this->getInstance($this->id)
                    ->__join($class, $foreign_key, $original_key)
                    ->fetch($class)
                    ->filter(fn($obj) => !$obj->isEmpty());  // Avoid empty records
    }
}