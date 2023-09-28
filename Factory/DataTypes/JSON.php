<?php
namespace Clicalmani\Database\Factory\DataTypes;

trait JSON
{
    function json()
    {
        $this->data .= ' JSON';
        return $this;
    }
}
