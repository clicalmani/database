<?php

declare(strict_types=1);

namespace Database\Test\Feature\Fixtures\Entities;

use Clicalmani\Database\DataTypes\DateTime;
use Clicalmani\Database\DataTypes\Integer;
use Clicalmani\Database\DataTypes\TinyInt;
use Clicalmani\Database\DataTypes\VarChar;
use Clicalmani\Database\Factory\Entity;
use Clicalmani\Database\Factory\PrimaryKey;
use Clicalmani\Database\Factory\Property;

class SoftUserEntity extends Entity
{
    #[Property(length: 10, unsigned: true, autoIncrement: true), PrimaryKey]
    public Integer $id;

    #[Property(length: 191)]
    public ?VarChar $name;

    #[Property(length: 200)]
    public ?VarChar $email;

    #[Property(length: 3, unsigned: true)]
    public TinyInt|int $age = 0;

    #[Property(length: 3, unsigned: true)]
    public TinyInt|int $active = 1;

    #[Property]
    public ?DateTime $deleted_at;
}