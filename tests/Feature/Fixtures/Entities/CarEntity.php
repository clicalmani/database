<?php

declare(strict_types=1);

namespace Database\Test\Feature\Fixtures\Entities;

use Clicalmani\Database\DataTypes\Integer;
use Clicalmani\Database\DataTypes\VarChar;
use Clicalmani\Database\Factory\Entity;
use Clicalmani\Database\Factory\PrimaryKey;
use Clicalmani\Database\Factory\Property;

class CarEntity extends Entity
{
    #[Property(length: 10, unsigned: true, autoIncrement: true), PrimaryKey]
    public Integer $id;

    #[Property(length: 10, unsigned: true)]
    public Integer $mechanic_id;

    #[Property(length: 191)]
    public VarChar $brand;
}