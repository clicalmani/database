<?php

declare(strict_types=1);

namespace Database\Test\Feature\Fixtures\Models;

use Clicalmani\Core\Acme\Model;
use Database\Test\Feature\Fixtures\Entities\RoleEntity;

class Role extends Model
{
    protected string $table = 'roles';
    protected string|array $primaryKey = 'id';
    protected string $entity = RoleEntity::class;
}