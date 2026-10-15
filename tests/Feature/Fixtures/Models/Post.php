<?php

declare(strict_types=1);

namespace Database\Test\Feature\Fixtures\Models;

use Clicalmani\Core\Acme\Model;
use Clicalmani\Database\Factory\Models\Relations\BelongsTo;
use Clicalmani\Database\Factory\Models\Relations\MorphMany;
use Clicalmani\Database\Factory\Models\Relations\MorphToMany;
use Database\Test\Feature\Fixtures\Entities\PostEntity;

class Post extends Model
{
    protected string $table = 'posts';
    protected string|array $primaryKey = 'id';
    protected string $entity = PostEntity::class;

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function comments(): MorphMany
    {
        return $this->morphMany(Comment::class, 'commentable');
    }

    public function tags(): MorphToMany
    {
        return $this->morphToMany(Tag::class, 'taggable');
    }
}