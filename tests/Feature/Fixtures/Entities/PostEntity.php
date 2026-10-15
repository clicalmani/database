<?php

declare(strict_types=1);

namespace Database\Test\Feature\Fixtures\Entities;

use Clicalmani\Database\DataTypes\Integer;
use Clicalmani\Database\DataTypes\Text;
use Clicalmani\Database\DataTypes\VarChar;
use Clicalmani\Database\Factory\PrimaryKey;
use Clicalmani\Database\Factory\Property;
use Clicalmani\Database\Factory\Entity;

class PostEntity extends Entity
{
    #[Property(length: 10, unsigned: true, autoIncrement: true), PrimaryKey]
    public Integer $id;

    #[Property(length: 10, unsigned: true)]
    public Integer $user_id;

    #[Property(length: 191)]
    public ?VarChar $title;

    #[Property]
    public ?Text $body;
}