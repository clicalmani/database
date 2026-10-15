<?php

declare(strict_types=1);

namespace Database\Test\Feature\Fixtures\Models;

use Clicalmani\Core\Acme\Model;
use Database\Test\Feature\Fixtures\Entities\AttrUserEntity;

class AttrUser extends Model
{
    protected string $table = 'attr_users';
    protected string|array $primaryKey = 'id';
    protected string $entity = AttrUserEntity::class;

    protected array $hidden = ['email'];
}