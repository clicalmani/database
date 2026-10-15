<?php

declare(strict_types=1);

namespace Database\Test\Feature\Fixtures\Models;

use Clicalmani\Core\Acme\Model;
use Clicalmani\Database\Factory\Models\Relations\BelongsTo;
use Clicalmani\Database\Factory\Models\Relations\BelongsToMany;
use Clicalmani\Database\Factory\Models\Relations\HasMany;
use Clicalmani\Database\Factory\Models\Relations\HasOne;
use Clicalmani\Database\Factory\Models\Relations\MorphMany;
use Clicalmani\Database\Factory\Models\Relations\MorphOne;
use Clicalmani\Database\Factory\Models\Relations\MorphToMany;
use Database\Test\Feature\Fixtures\Entities\UserEntity;

class User extends Model
{
    protected string $table = 'users';
    protected string|array $primaryKey = 'id';
    protected string $entity = UserEntity::class;

    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }

    public function posts(): HasMany
    {
        return $this->hasMany(Post::class);
    }

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class);
    }

    public function profile(): HasOne
    {
        return $this->hasOne(Profile::class);
    }

    public function avatar(): MorphOne
    {
        return $this->morphOne(Image::class, 'imageable');
    }

    public function images(): MorphMany
    {
        return $this->morphMany(Image::class, 'imageable');
    }

    public function comments(): MorphMany
    {
        return $this->morphMany(Comment::class, 'commentable');
    }

    public function tags(): MorphToMany
    {
        return $this->morphToMany(Tag::class, 'taggable');
    }

    public function account(): HasOne
    {
        return $this->hasOne(Account::class);
    }
}