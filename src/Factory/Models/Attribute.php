<?php
namespace Clicalmani\Database\Factory\Models;

class Attribute
{
    /**
     * Reading mode
     * 
     * @var int
     */
    const READ = 0;

    /**
     * Update writing mode
     * 
     * @var int
     */
    const UPDATE = 1;

    /**
     * Insert writing mode
     * 
     * @var int
     */
    const INSERT = 2;

    /**
     * Attribute name
     * 
     * @var string
     */
    private $name;

    /**
     * Attribute value
     * 
     * @var mixed
     */
    private $value;

    /**
     * Attribute writing mode
     * 
     * @var int
     */
    private $access;

    /**
     * Model
     * 
     * @var \Clicalmani\Database\Factory\Models\Model
     */
    private $model;

    /**
     * Model entity
     * 
     * @var \Clicalmani\Database\Factory\Entity
     */
    private $entity;

    public function __construct(string $name, mixed $value = null, ?int $access = 2)
    {
        $this->name = $name;
        $this->access = $access;
        $this->value = $value;
    }

    /**
     * A wrapper for attribute update.
     * 
     * @param mixed $new_value Attribute new value
     * @return void
     */
    private function update(mixed $new_value) : void
    {
        $this->value = $new_value;
        $this->entity->setProperty($this->name, $new_value);
    }

    /**
     * Verify if data is null.
     * 
     * @return bool
     */
    public function isNull() : bool
    {
        return is_null($this->value);
    }

    /**
     * Verify whether it is a fillable attribute.
     * 
     * @return bool
     */
    public function isFillable() : bool
    {
        if ( empty($this->model->getFillableAttributes()) ) return true;

        return !!in_array($this->name, $this->model->getFillableAttributes());
    }

    /**
     * Verify whether it is a hidden attribute.
     * 
     * @return bool
     */
    public function isHidden() : bool
    {
        if ( empty($this->model->getHiddenAttributes()) ) return false;

        return !!in_array($this->name, $this->model->getHiddenAttributes());
    }

    /**
     * Verify whether it's a custom attribute
     * 
     * @return bool
     */
    public function isCustom() : bool
    {
        return !!in_array($this->name, $this->model->getCustomAttributes());
    }

    /**
     * Customize attribute
     * 
     * @return string
     */
    public function customize() : string
    {
        return 'get' . collection( explode('_', $this->name) )
                    ->map(fn(string $value) => ucfirst($value))
                    ->join('') 
                    . 'Attribute';
    }

    /**
     * Return custom attribute value
     * 
     * @return mixed
     */
    public function getCustomValue() : mixed
    {
        $custmized = $this->customize();

        if ( method_exists($this->model, $custmized) ) {
            return $this->model->{$custmized}();
        }

        return null;
    }

    public function __set(mixed $name, mixed $value)
    {
        switch ($name) {
            case 'name': $this->name = $value; break;
            case 'value': $this->update($value); break;
            case 'access': $this->access = $value; break;
            case 'entity': 
                $this->entity = $value; 
                $this->entity->{$this->name} = $this->value;
            break;
            case 'model': 
                $this->model = $value; 
                $this->entity = $this->model->getEntity();
                $this->entity->setModel($value);
            break;
        }
    }

    public function __get(mixed $name)
    {
        switch ($name) {
            case 'name': return $this->name;
            case 'value': return $this->value;
            case 'access': return $this->access;
            case 'entity': return $this->entity;
            case 'model': return $this->model;
        }
    }

    public function __toString()
    {
        return $this->name;
    }
}
