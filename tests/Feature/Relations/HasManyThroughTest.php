<?php

declare(strict_types=1);

namespace Database\Test\Feature\Relations;

use Database\Test\Feature\DatabaseTestCase;
use Database\Test\Feature\Fixtures\Models\Country;
use Database\Test\Feature\Fixtures\Models\Post;

final class HasManyThroughTest extends DatabaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->seedDatabase();
        $this->seedAdvancedDatabase();
    }

    public function test_has_many_through_returns_collection(): void
    {
        // Country 1 → Users (1, 2) → Posts (6 posts au total)
        $country = Country::find(1);
        $posts = $country->posts;

        $this->assertGreaterThan(0, count($posts));
    }

    public function test_has_many_through_returns_only_related_posts(): void
    {
        $country = Country::find(1);
        $posts = $country->posts;

        // Récupère les user_ids des users du pays 1
        $userIds = $this->pdo->query(
            'SELECT id FROM users WHERE country_id = 1'
        )->fetchAll(\PDO::FETCH_COLUMN);

        foreach ($posts as $post) {
            $this->assertContains((int) $post->user_id, array_map('intval', $userIds));
        }
    }

    public function test_has_many_through_returns_empty_when_no_match(): void
    {
        // Country 3 (Japan) → Aucun user
        $country = Country::find(3);
        $posts = $country->posts;

        $this->assertCount(0, $posts);
    }

    public function test_has_many_through_isolates_by_country(): void
    {
        $country1Posts = Country::find(1)->posts;
        $country2Posts = Country::find(2)->posts;

        // Countries différents → posts différents
        $ids1 = array_map(fn($p) => (int) $p->id, is_array($country1Posts) ? $country1Posts : $country1Posts->toArray());
        $ids2 = array_map(fn($p) => (int) $p->id, is_array($country2Posts) ? $country2Posts : $country2Posts->toArray());

        $this->assertEmpty(array_intersect($ids1, $ids2));
    }
}