<?php

declare(strict_types=1);

namespace Database\Test\Feature\Fixtures\Models;

use Clicalmani\Core\Acme\Model;
use Database\Test\Feature\Fixtures\Entities\ImageEntity;

class Image extends Model
{
    protected string $table = 'images';
    protected string|array $primaryKey = 'id';
    protected string $entity = ImageEntity::class;
}