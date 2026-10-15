<?php

declare(strict_types=1);

namespace Database\Test\Feature\Relations;

use Database\Test\Feature\DatabaseTestCase;
use Database\Test\Feature\Fixtures\Models\Post;
use Database\Test\Feature\Fixtures\Models\User;

final class BelongsToTest extends DatabaseTestCase
{
    public function test_belongs_to_returns_parent(): void
    {
        $this->seedDatabase();

        $post = Post::find(1);
        $user = $post->user;

        $this->assertInstanceOf(User::class, $user);
        $this->assertSame((int) $post->user_id, (int) $user->id);
    }

    public function test_belongs_to_uses_foreign_key_convention(): void
    {
        $this->seedDatabase();

        $post = Post::find(2);
        $user = $post->user;

        $this->assertSame((int) $post->user_id, (int) $user->id);
    }
}