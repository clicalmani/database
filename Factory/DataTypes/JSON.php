<?php
namespace Clicalmani\Flesco\Database\Factory\DataTypes;

trait JSON
{
    function json()
    {
        $this->data .= ' JSON';
        return $this;
    }
}
