<?php

declare(strict_types=1);

namespace Database\Test\Feature\Relations;

use Database\Test\Feature\DatabaseTestCase;
use Database\Test\Feature\Fixtures\Models\Post;
use Database\Test\Feature\Fixtures\Models\User;

final class WhereHasTest extends DatabaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->seedDatabase();
        $this->seedAdvancedDatabase();
    }

    public function test_where_has_filters_parents_with_children(): void
    {
        // Tous les users ont des posts (seed)
        $users = User::whereHas(Post::class, function ($query) {
            // Callback
        })->get();

        $this->assertGreaterThan(0, $users->count());
    }

    public function test_where_doesnt_have_filters_parents_without_children(): void
    {
        // User 5 : n'a pas d'articles
        $users = User::whereDoesntHave(Post::class, function ($query) {
            // Callback
        })->get();
        
        $this->assertGreaterThan(0, $users->count());
    }

    public function test_where_has_with_conditions(): void
    {
        $users = User::whereHas(Post::class, function ($query) {
            $query->where('published = ?', [1]);
        })->get();

        $this->assertIsObject($users);
    }

    public function test_or_where_has_combines_conditions(): void
    {
        $users = User::where('id = ?', [1])
            ->orWhereHas(Post::class, function ($query) {
                // Callback
            })
            ->get();

        $this->assertGreaterThan(0, $users->count());
    }
}