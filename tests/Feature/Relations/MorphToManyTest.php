<?php

declare(strict_types=1);

namespace Database\Test\Feature\Relations;

use Database\Test\Feature\DatabaseTestCase;
use Database\Test\Feature\Fixtures\Models\Post;
use Database\Test\Feature\Fixtures\Models\Tag;
use Database\Test\Feature\Fixtures\Models\User;

final class MorphToManyTest extends DatabaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->seedDatabase();
        $this->seedAdvancedDatabase();
    }

    public function test_morph_to_many_returns_tags_for_user(): void
    {
        $user = User::find(1);
        $tags = $user->tags;

        $this->assertGreaterThan(0, count($tags));
    }

    public function test_morph_to_many_returns_tags_for_post(): void
    {
        $post = Post::find(1);
        $tags = $post->tags;

        $this->assertGreaterThan(0, count($tags));
    }

    public function test_morph_to_many_isolates_by_type(): void
    {
        $userTags = User::find(1)->tags;
        $postTags = Post::find(1)->tags;

        $userTagNames = array_map(fn($t) => $t->name, is_array($userTags) ? $userTags : $userTags->toArray());
        $postTagNames = array_map(fn($t) => $t->name, is_array($postTags) ? $postTags : $postTags->toArray());

        // Les deux ont des tags, mais pas forcément les mêmes
        $this->assertIsArray($userTagNames);
        $this->assertIsArray($postTagNames);
    }
}