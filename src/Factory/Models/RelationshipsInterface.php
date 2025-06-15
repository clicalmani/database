<?php
namespace Clicalmani\Database\Factory\Models;

interface RelationshipsInterface
{
    /**
     * Pivot table relationship
     * 
     * @param string $class Child model
     * @param ?string $foreign_key Pivot table foreign key
     * @param ?string $parent_key Pivot table original key
     * @return \Clicalmani\Database\Factory\Models\ModelInterface
     */
    public function pivotRight(string $class, ?string $foreign_key = null, ?string $parent_key = null) : \Clicalmani\Database\Factory\Models\ModelInterface;

    /**
     * Pivot table relationship
     * 
     * @param string $class Child model
     * @param string $foreign_key Pivot table foreign key
     * @param string $parent_key Pivot table original key
     * @return \Clicalmani\Database\Factory\Models\ModelInterface
     */
    public function pivotLeft(string $class, ?string $foreign_key = null, ?string $parent_key = null) : \Clicalmani\Database\Factory\Models\ModelInterface;

    /**
     * Pivot table relationship
     * 
     * @param string $class Child model
     * @param ?string $foreign_key Pivot table foreign key
     * @param ?string $parent_key Pivot table original key
     * @return \Clicalmani\Database\Factory\Models\ModelInterface
     */
    public function pivotInner(string $class, ?string $foreign_key = null, ?string $parent_key = null) : \Clicalmani\Database\Factory\Models\ModelInterface;

    /**
     * Pivot table relationship
     * 
     * @param string $class Child model
     * @param ?string $foreign_key Pivot table foreign key
     * @param ?string $parent_key Pivot table original key
     * @return \Clicalmani\Database\Factory\Models\ModelInterface
     */
    public function pivotOuter(string $class, ?string $foreign_key = null, ?string $parent_key = null) : \Clicalmani\Database\Factory\Models\ModelInterface;

    /**
     * Pivot table relationship
     * 
     * @param string $class Child model
     * @param ?string $foreign_key Pivot table foreign key
     * @param ?string $parent_key Pivot table original key
     * @return \Clicalmani\Database\Factory\Models\ModelInterface
     */
    public function pivotCross(string $class, ?string $foreign_key = null, ?string $parent_key = null) : \Clicalmani\Database\Factory\Models\ModelInterface;
}