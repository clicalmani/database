<?php
namespace Clicalmani\Database\Factory\Models;

use Clicalmani\Database\JoinClause;

trait RelationShips
{
    /**
     * The current model inherit a foreign key
     * We should match the model key value to obtain its parent.
     * 
     * @param string $class Parent model
     * @param ?string $foreign_key [Optional] Table foreign key
     * @param ?string $parent_key [Optional] Original key
     * @return mixed
     */
    protected function belongsTo(string $class, ?string $foreign_key = null, ?string $parent_key = null) : mixed
    {
        return ( new $class )->__join($this, ...$this->guessRelationshipKeys($foreign_key, $parent_key, $class))
                    ->whereAnd($this->getKeySQLCondition(true))
                    ->fetch()
                    ->first();
    }

    /**
     * One an one relationship: the current model inherit a foreign key
     * 
     * @param string $class Parent model
     * @param ?string $foreign_key [Optional] Table foreign key
     * @param ?string $parent_key [Optional] original key
     * @return mixed
     */
    protected function hasOne(string $class, ?string $foreign_key = null, ?string $parent_key = null) : mixed
    {
        if ( $this->isEmpty() ) return null;
        
        return $this->__join($class, $foreign_key, $parent_key)
                    ->fetch($class)
                    ->first();
    }

    /**
     * One to many relationship
     * 
     * @param string $class Child model
     * @param ?string $foreign_key [Optional] Table foreign key
     * @param ?string $parent_key [Optional] Original key
     * @return \Clicalmani\Foundation\Collection\Collection
     */
    protected function hasMany(string $class, ?string $foreign_key = null, ?string $parent_key = null) : \Clicalmani\Foundation\Collection\Collection
    {
        if ( $this->isEmpty() ) return collection();
        
        return $this->getInstance($this->id)
                    ->__join($class, $foreign_key, $parent_key)
                    ->fetch($class)
                    ->filter(fn($obj) => !$obj->isEmpty());  // Avoid empty records
    }

    /**
     * Many to many relationship
     * 
     * @param string $class Child model
     * @param ?string $foreign_key [Optional] Table foreign key
     * @param ?string $parent_key [Optional] Original key
     * @return \Clicalmani\Foundation\Collection\Collection
     */
    protected function belongsToMany(string $class, ?string $foreign_key = null, ?string $parent_key = null) : \Clicalmani\Foundation\Collection\Collection
    {
        if ( $this->isEmpty() ) return collection();

        [$foreign_key, $parent_key] = $this->guessRelationshipKeys($foreign_key, $parent_key);

        $this->query->where($this->getKey(true) . ' = ? ', [$this->id]);

        $this->query->join((new $class)->getTable(true), function(JoinClause $join) use ($foreign_key, $parent_key) {
            $join->right()->on("$foreign_key=$parent_key");
        });

        return $this->fetch($class);
    }

    /**
     * Has one through relationship
     * 
     * @param string $class Child model
     * @param ?string $foreign_key [Optional] Foreign key
     * @param ?string $parent_key [Optional] Original key
     * @return mixed
     */
    protected function hasOneThrough(string $class, string $pivot_class, ?string $foreign_key = null, ?string $parent_key = null, ?string $pivot_foreign_key = null, ?string $pivot_parent_key = null) : mixed
    {
        if ( $this->isEmpty() ) return null;
        
        [$foreign_key, $parent_key] = $this->guessRelationshipKeys($foreign_key, $parent_key, $pivot_class); 
        [$pivot_foreign_key, $pivot_parent_key] = $this->guessRelationshipKeys($pivot_foreign_key, $pivot_parent_key); 

        $this->query->where($this->getKey(true) . ' = ? ', [$this->id]);

        $this->query->join((new $pivot_class)->getTable(true), function($join) use ($pivot_foreign_key, $pivot_parent_key) { 
            $join->right()->on("$pivot_foreign_key=$pivot_parent_key");
        });

        $this->query->join((new $class)->getTable(true), function($join) use ($foreign_key, $parent_key) { 
            $join->right()->on("$foreign_key=$parent_key");
        });

        return $this->fetch($class)->first();
    }

    /**
     * Has many through relationship
     * 
     * @param string $class Child model
     * @param ?string $foreign_key [Optional] Table foreign key
     * @param ?string $parent_key [Optional] Original key
     * @return \Clicalmani\Foundation\Collection\Collection
     */
    protected function hasManyThrough(string $class, string $pivot_class, ?string $foreign_key = null, ?string $parent_key = null, ?string $pivot_foreign_key = null, ?string $pivot_parent_key = null) : \Clicalmani\Foundation\Collection\Collection
    {
        if ( $this->isEmpty() ) return collection();
        
        [$foreign_key, $parent_key] = $this->guessRelationshipKeys($foreign_key, $parent_key, $pivot_class); 
        [$pivot_foreign_key, $pivot_parent_key] = $this->guessRelationshipKeys($pivot_foreign_key, $pivot_parent_key); 

        $this->query->where($this->getKey(true) . ' = ? ', [$this->id]);

        $this->query->join((new $pivot_class)->getTable(true), function($join) use ($pivot_foreign_key, $pivot_parent_key) { 
            $join->right()->on("$pivot_foreign_key=$pivot_parent_key");
        });

        $this->query->join((new $class)->getTable(true), function($join) use ($foreign_key, $parent_key) { 
            $join->right()->on("$foreign_key=$parent_key");
        });

        return $this->fetch($class);
    }

    /**
     * Polymorphic relationship
     * 
     * @return mixed
     */
    protected function morphTo() : mixed
    {
        $this->query->where($this->getKey(true) . ' = ? ', [$this->id]);
        return $this->first();
    }

    /**
     * One-to-One morphic relationship
     * 
     * @param string $class Child model
     * @param string $morphic Morphic association
     * @return mixed
     */
    protected function morphOne(string $class, string $morphic) : mixed
    {
        if ( $this->isEmpty() ) return null;
        
        $file = debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT, 2)[0]['file'];

        $this->query->set('tables', [(new $class)->getTable(true)]);
        $this->query->where("{$morphic}_id = ? AND {$morphic}_type = ? ", [$this->id, strtolower(pathinfo($file, PATHINFO_FILENAME))]);
        
        return $this->fetch($class)->first();
    }

    /**
     * One-to-Many morphic relationship
     * 
     * @param string $class Child model
     * @param string $morphic Morphic association
     * @return \Clicalmani\Foundation\Collection\Collection
     */
    protected function morphMany(string $class, string $morphic) : \Clicalmani\Foundation\Collection\Collection
    {
        if ( $this->isEmpty() ) return collection();

        $this->query->set('tables', [(new $class)->getTable(true)]);
        $this->query->where("{$morphic}_id = ? AND {$morphic}_type = ? ", [$this->id, strtolower(debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT, 2)[1]['function'])]);

        return $this->fetch();
    }

    /**
     * Many-to-Many morphic relationship
     * 
     * @param string $class 
     * @param string $morphic Morphic association
     * @return \Clicalmani\Foundation\Collection\Collection
     */
    protected function morphToMany(string $class, string $morphic) : \Clicalmani\Foundation\Collection\Collection
    {
        if ( $this->isEmpty() ) return collection();

        $this->query->where("{$morphic}_id = ? {$morphic}_type = ? ", [$this->id, strtolower(debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT, 2)[1]['function'])]);

        $this->query->join("{$morphic}s", function(JoinClause $join) {
            $join->right()->using($this->getKey());
        });

        $obj = new $class;

        $this->query->join($obj->getTable(true), function(JoinClause $join) use($obj, $morphic) {
            $join->right()->on("{$morphic}_id = {$obj->getKey()}");
        });

        return $this->fetch($class);
    }

    /**
     * Many-by-Many morphic relationship
     * 
     * @param string $class 
     * @param string $morphic Morphic association
     * @return \Clicalmani\Foundation\Collection\Collection
     */
    protected function morphedByMany(string $class, string $morphic) : \Clicalmani\Foundation\Collection\Collection
    {
        if ( $this->isEmpty() ) return collection();

        $this->query->where("{$morphic}_id = ? {$morphic}_type = ? ", [$this->id, strtolower(debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT, 2)[1]['function'])]);

        $obj = new $class;

        $this->query->join("{$morphic}s", function(JoinClause $join) use($obj, $morphic) {
            $join->right()->on("{$morphic}_id = {$obj->getKey()}");
        });

        $this->query->join((new $class)->getTable(true), function(JoinClause $join) {
            $join->right()->using($this->getKey());
        });

        return $this->fetch($class);
    }

    /**
     * Pivot table relationship
     * 
     * @param string $class Child model
     * @param ?string $foreign_key [Optional] Pivot table foreign key
     * @param ?string $parent_key [Optional] Pivot table original key
     * @param string $direction [Optional] Join direction
     * @return static
     */
    private function __pivot(string $class, ?string $foreign_key = null, ?string $parent_key = null, ?string $direction = 'left') : static
    {
        [$foreign_key, $parent_key] = $this->guessRelationshipKeys($foreign_key, $parent_key);

        $this->query->join((new $class)->getTable(true), function(JoinClause $join) use ($foreign_key, $parent_key, $direction) {
            if ($direction !== 'cross') $join->{$direction}()->on("$foreign_key=$parent_key");
            else $join->{$direction}();
        });

        return $this;
    }

    /**
     * Pivot table relationship
     * 
     * @param string $class Child model
     * @param ?string $foreign_key Pivot table foreign key
     * @param ?string $parent_key Pivot table original key
     * @return static
     */
    public function pivotRight(string $class, ?string $foreign_key = null, ?string $parent_key = null) : static
    {
        return $this->__pivot($class, $foreign_key, $parent_key, 'right');
    }

    /**
     * Pivot table relationship
     * 
     * @param string $class Child model
     * @param string $foreign_key Pivot table foreign key
     * @param string $parent_key Pivot table original key
     * @return static
     */
    public function pivotLeft(string $class, ?string $foreign_key = null, ?string $parent_key = null) : static
    {
        return $this->__pivot($class, $foreign_key, $parent_key, 'left');
    }

    /**
     * Pivot table relationship
     * 
     * @param string $class Child model
     * @param ?string $foreign_key Pivot table foreign key
     * @param ?string $parent_key Pivot table original key
     * @return static
     */
    public function pivotInner(string $class, ?string $foreign_key = null, ?string $parent_key = null) : static
    {
        return $this->__pivot($class, $foreign_key, $parent_key, 'inner');
    }

    /**
     * Pivot table relationship
     * 
     * @param string $class Child model
     * @param ?string $foreign_key Pivot table foreign key
     * @param ?string $parent_key Pivot table original key
     * @return static
     */
    public function pivotOuter(string $class, ?string $foreign_key = null, ?string $parent_key = null) : static
    {
        return $this->__pivot($class, $foreign_key, $parent_key, 'outer');
    }

    /**
     * Pivot table relationship
     * 
     * @param string $class Child model
     * @param ?string $foreign_key Pivot table foreign key
     * @param ?string $parent_key Pivot table original key
     * @return static
     */
    public function pivotCross(string $class, ?string $foreign_key = null, ?string $parent_key = null) : static
    {
        return $this->__pivot($class, $foreign_key, $parent_key, 'cross');
    }
}