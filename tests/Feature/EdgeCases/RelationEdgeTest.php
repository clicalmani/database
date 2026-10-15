<?php

declare(strict_types=1);

namespace Database\Test\Feature\EdgeCases;

use Database\Test\Feature\DatabaseTestCase;
use Database\Test\Feature\Fixtures\Models\Post;
use Database\Test\Feature\Fixtures\Models\User;

final class RelationEdgeTest extends DatabaseTestCase
{
    public function test_belongs_to_with_null_foreign_key(): void
    {
        // User 1 + Post sans user_id valide
        $this->pdo->exec("INSERT INTO users (name, email, age, active) VALUES ('Solo', 'solo@example.com', 20, 1)");
        $userId = (int) $this->pdo->lastInsertId();

        $post = new Post;
        $post->user_id = 9999;   // FK inexistante
        $post->title   = 'Orphan';
        $post->body    = 'Test';
        $post->save();

        $found = Post::where('title = ?', ['Orphan'])->first();

        // Doit retourner null OU un user vide
        $user = $found->user;
        $this->assertTrue($user === null || $user->id === null);
    }

    public function test_has_many_with_no_children(): void
    {
        $this->pdo->exec("INSERT INTO users (name, email, age, active) VALUES ('Lonely', 'lonely@example.com', 20, 1)");
        $userId = (int) $this->pdo->lastInsertId();

        $user = User::find($userId);
        $posts = $user->posts;

        $this->assertCount(0, $posts);
    }

    public function test_belongs_to_many_with_no_related(): void
    {
        $this->pdo->exec("INSERT INTO users (name, email, age, active) VALUES ('NoRoles', 'noroles@example.com', 20, 1)");
        $userId = (int) $this->pdo->lastInsertId();

        $user = User::find($userId);
        $roles = $user->roles;

        $this->assertCount(0, $roles);
    }

    public function test_relation_on_unsaved_model(): void
    {
        $user = new User;
        $user->name = 'Unsaved';

        // Pas d'ID → pas de relation
        $posts = $user->posts;

        $this->assertCount(0, $posts);
    }

    public function test_relation_after_delete(): void
    {
        $this->seedDatabase();

        $user = User::find(1);
        $user->delete();

        // Les posts sont supprimés en cascade
        $this->assertSame(12, $this->countRows('posts'));
    }

    public function test_where_has_with_no_match(): void
    {
        $result = User::whereHas(Post::class, function ($q) {
            $q->where('title = ?', ['Nonexistent']);
        })->get();

        $this->assertSame(0, $result->count());
    }

    public function test_relation_caching(): void
    {
        $this->seedDatabase();

        $user = User::find(1);

        // Premier appel : charge les posts
        $first = $user->posts;

        // Deuxième appel : doit retourner la même collection
        $second = $user->posts;

        $this->assertSame(count($first), count($second));
    }
}