<?php
namespace Clicalmani\Database\Factory\DataTypes;

/**
 * Trait Spatial
 * 
 * @package Clicalmani\Database
 * @author @clicalmani
 */
trait Spatial
{
    /**
     * Geometry data type
     * 
     * @return static
     */
    public function geometry() : static
    {
        $this->data .= ' GEOMETRY';
        return $this;
    }

    /**
     * Point data type
     * 
     * @return static
     */
    public function point() : static
    {
        $this->data .= ' POINT';
        return $this;
    }

    /**
     * Linestring data type
     * 
     * @return static
     */
    public function lineString() : static
    {
        $this->data .= ' LINESTRING';
        return $this;
    }

    /**
     * Polygone data type
     * 
     * @return static
     */
    public function polygone() : static
    {
        $this->data .= ' POLYGONE';
        return $this;
    }

    /**
     * MultiPoint data type
     * 
     * @return static
     */
    public function multiPoint() : static
    {
        $this->data .= ' MULTIPOINT';
        return $this;
    }

    /**
     * MultiLineString data type
     * 
     * @return static
     */
    public function multiLineString() : static
    {
        $this->data .= ' MULTILINESTRING';
        return $this;
    }

    /**
     * SRID data type
     * 
     * @param string $srid
     * @return static
     */
    public function srid(string $srid) : static
    {
        $this->data .= " SRID $srid";
        return $this;
    }
}
