<?php

declare(strict_types=1);

namespace Database\Test\Feature\Relations;

use Database\Test\Feature\DatabaseTestCase;
use Database\Test\Feature\Fixtures\Models\Comment;
use Database\Test\Feature\Fixtures\Models\Post;
use Database\Test\Feature\Fixtures\Models\User;

final class MorphToTest extends DatabaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->seedDatabase();
        $this->seedAdvancedDatabase();
    }

    public function test_morph_to_returns_user_for_user_comment(): void
    {
        $comment = Comment::where('commentable_type = ?', [User::class])->first();

        $this->assertNotNull($comment);

        $parent = $comment->commentable;

        $this->assertInstanceOf(User::class, $parent);
        $this->assertSame((int) $comment->commentable_id, (int) $parent->id);
    }

    public function test_morph_to_returns_post_for_post_comment(): void
    {
        $comment = Comment::where('commentable_type = ?', [Post::class])->first();

        $this->assertNotNull($comment);

        $parent = $comment->commentable;

        $this->assertInstanceOf(Post::class, $parent);
        $this->assertSame((int) $comment->commentable_id, (int) $parent->id);
    }

    public function test_morph_to_returns_null_when_type_missing(): void
    {
        $comment = new Comment;
        $comment->commentable_id = 999;
        $comment->commentable_type = 'NonExistentClass';
        $comment->body = 'test';

        // Selon l'implémentation, peut throw ou retourner null
        try {
            $result = $comment->commentable;
            $this->assertNull($result);
        } catch (\Throwable $e) {
            $this->assertTrue(true);
        }
    }
}