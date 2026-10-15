<?php

declare(strict_types=1);

namespace Database\Test\Feature\Fixtures\Models;

use Clicalmani\Core\Acme\Model;
use Clicalmani\Database\Factory\Models\Relations\HasMany;
use Clicalmani\Database\Factory\Models\Relations\HasManyThrough;
use Database\Test\Feature\Fixtures\Entities\CountryEntity;

class Country extends Model
{
    protected string $table = 'countries';
    protected string|array $primaryKey = 'id';
    protected string $entity = CountryEntity::class;

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function posts(): HasManyThrough
    {
        return $this->hasManyThrough(Post::class, User::class);
    }
}