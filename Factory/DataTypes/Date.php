<?php
namespace Clicalmani\Database\Factory\DataTypes;

trait Date
{
    function date()
    {
        $this->data .= ' DATE';
        return $this;
    }

    function dateTime()
    {
        $this->data .= ' DATETIME';
        return $this;
    }

    function time()
    {
        $this->data .= ' TIME';
        return $this;
    }

    function timestamp()
    {
        $this->data .= ' TIMESTAMP';
        return $this;
    }
}
