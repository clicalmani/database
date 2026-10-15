<?php

declare(strict_types=1);

namespace Database\Test\Feature\Fixtures\Entities;

use Clicalmani\Database\DataTypes\Integer;
use Clicalmani\Database\DataTypes\Text;
use Clicalmani\Database\DataTypes\VarChar;
use Clicalmani\Database\Factory\Entity;
use Clicalmani\Database\Factory\PrimaryKey;
use Clicalmani\Database\Factory\Property;

class CommentEntity extends Entity
{
    #[Property(length: 10, unsigned: true, autoIncrement: true), PrimaryKey]
    public Integer $id;

    #[Property(length: 10, unsigned: true)]
    public Integer $commentable_id;

    #[Property(length: 191)]
    public VarChar $commentable_type;

    #[Property]
    public Text $body;

    #[Property(length: 10, unsigned: true)]
    public ?Integer $author_id;
}