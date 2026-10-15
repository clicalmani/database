<?php

declare(strict_types=1);

namespace Database\Test\Feature\Fixtures\Models;

use Clicalmani\Core\Acme\Model;
use Clicalmani\Database\Traits\SoftDelete;
use Database\Test\Feature\Fixtures\Entities\SoftUserEntity;

class SoftUser extends Model
{
    use SoftDelete;

    protected string $table = 'soft_users';
    protected string|array $primaryKey = 'id';
    protected string $entity = SoftUserEntity::class;
}