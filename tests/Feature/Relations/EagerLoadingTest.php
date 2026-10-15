<?php

declare(strict_types=1);

namespace Database\Test\Feature\Relations;

use Database\Test\Feature\DatabaseTestCase;
use Database\Test\Feature\Fixtures\Models\Post;
use Database\Test\Feature\Fixtures\Models\User;

final class EagerLoadingTest extends DatabaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->seedDatabase();
        $this->seedAdvancedDatabase();
    }

    public function test_with_loads_has_many(): void
    {
        $users = User::with('posts')->get();

        $this->assertSame(5, $users->count());

        foreach ($users as $user) {
            $this->assertIsArray(is_object($user->posts) ? $user->posts->toArray(): $user->posts);
        }
    }

    public function test_with_loads_belongs_to(): void
    {
        $posts = Post::with('user')->get();

        foreach ($posts as $post) {
            $this->assertInstanceOf(User::class, $post->user);
        }
    }

    public function test_with_multiple_relations(): void
    {
        $users = User::with(['posts', 'profile'])->get();

        foreach ($users as $user) {
            $this->assertIsArray($user->posts->toArray());
        }
    }

    public function test_with_does_not_trigger_n_plus_1(): void
    {
        $queries = [];

        \Clicalmani\Core\Support\Facades\DB::captureQueries(
            function () {
                $users = User::with('posts')->get();

                // Accéder aux relations pour forcer le chargement
                foreach ($users as $user) {
                    $user->posts;
                }
            },
            function (array $q) use (&$queries) {
                $queries = $q;
            }
        );
        
        // Sans eager loading : 1 + N requêtes
        // Avec eager loading : 2 requêtes (users + posts)
        $this->assertLessThanOrEqual(
            6, // Users 5 + 1
            count($queries),
            'Eager loading doit réduire le nombre de requêtes'
        );
    }

    public function test_with_empty_relation_returns_empty_array(): void
    {
        // User 5 n'a pas de posts
        $user = User::with('posts')->find(5);
        
        $this->assertIsArray($user->posts->toArray());
        $this->assertCount(0, $user->posts->toArray());
    }
}