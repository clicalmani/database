<?php

declare(strict_types=1);

namespace Database\Test\Feature\Relations;

use Database\Test\Feature\DatabaseTestCase;
use Database\Test\Feature\Fixtures\Models\User;

final class HasManyTest extends DatabaseTestCase
{
    public function test_has_many_returns_collection(): void
    {
        $this->seedDatabase();

        $user = User::find(1);
        $posts = $user->posts;

        $this->assertCount(3, $posts);
    }

    public function test_has_many_uses_foreign_key_convention(): void
    {
        $this->seedDatabase();

        $user = User::find(2);
        $posts = $user->posts;

        foreach ($posts as $post) {
            $this->assertSame((int) $user->id, (int) $post->user_id);
        }
    }

    public function test_has_many_returns_empty_collection_when_no_children(): void
    {
        $this->seedDatabase();

        $this->pdo->exec("INSERT INTO users (name, email, age, active) VALUES ('NoPosts', 'np@example.com', 20, 1)");
        $userId = (int) $this->pdo->lastInsertId();

        $user = User::find($userId);
        $this->assertCount(0, $user->posts);
    }
}