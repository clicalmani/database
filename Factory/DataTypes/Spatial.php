<?php
namespace Clicalmani\Flesco\Database\Factory\DataTypes;

trait Spatial
{
    function geometry()
    {
        $this->data .= ' GEOMETRY';
    }

    function point()
    {
        $this->data .= ' POINT';
    }

    function lineString()
    {
        $this->data .= ' LINESTRING';
    }

    function polygone()
    {
        $this->data .= ' POLYGONE';
    }

    function multiPoint()
    {
        $this->data .= ' MULTIPOINT';
    }

    function multiLineString()
    {
        $this->data .= ' MULTILINESTRING';
    }

    function srid($srid)
    {
        $this->data .= ' SRID ' . $srid;
    }
}
