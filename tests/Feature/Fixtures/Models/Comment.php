<?php

declare(strict_types=1);

namespace Database\Test\Feature\Fixtures\Models;

use Clicalmani\Core\Acme\Model;
use Clicalmani\Database\Factory\Models\Relations\BelongsTo;
use Clicalmani\Database\Factory\Models\Relations\MorphTo;
use Database\Test\Feature\Fixtures\Entities\CommentEntity;

class Comment extends Model
{
    protected string $table = 'comments';
    protected string|array $primaryKey = 'id';
    protected string $entity = CommentEntity::class;

    public function commentable(): MorphTo
    {
        return $this->morphTo('commentable');
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id', 'id');
    }
}