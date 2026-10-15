<?php

declare(strict_types=1);

namespace Database\Test\Feature\Fixtures\Models;

use Clicalmani\Core\Acme\Model;
use Database\Test\Feature\Fixtures\Entities\TagEntity;

class Tag extends Model
{
    protected string $table = 'tags';
    protected string|array $primaryKey = 'id';
    protected string $entity = TagEntity::class;
}