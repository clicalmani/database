<?php

declare(strict_types=1);

namespace Database\Test\Feature\Relations;

use Database\Test\Feature\DatabaseTestCase;
use Database\Test\Feature\Fixtures\Models\Comment;
use Database\Test\Feature\Fixtures\Models\Post;
use Database\Test\Feature\Fixtures\Models\User;

final class MorphManyTest extends DatabaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->seedDatabase();
        $this->seedAdvancedDatabase();
    }

    public function test_morph_many_returns_collection(): void
    {
        $user = User::find(1);
        $comments = $user->comments;
        
        $this->assertGreaterThan(0, $comments->count());
    }

    public function test_morph_many_filters_by_type(): void
    {
        // Les comments du User 1 doivent avoir commentable_type = User
        $user = User::find(1);
        $comments = $user->comments;

        foreach ($comments as $comment) {
            $this->assertSame(User::class, $comment->commentable_type);
        }
    }

    public function test_morph_many_filters_by_id(): void
    {
        $user = User::find(1);
        $comments = $user->comments;

        foreach ($comments as $comment) {
            $this->assertSame((int) $user->id, (int) $comment->commentable_id);
        }
    }

    public function test_morph_many_returns_empty_when_no_match(): void
    {
        // User 5 n'a pas de commentaires
        $user = User::find(5);
        $comments = $user->comments;

        $this->assertCount(0, $comments);
    }

    public function test_morph_many_on_post(): void
    {
        $post = Post::find(1);
        $comments = $post->comments;

        $this->assertGreaterThan(0, count($comments));

        foreach ($comments as $comment) {
            $this->assertSame(Post::class, $comment->commentable_type);
        }
    }

    public function test_morph_many_isolates_types(): void
    {
        // User 1 et Post 1 ont tous les deux des commentaires,
        // mais avec des commentable_type différents.
        $userComments = User::find(1)->comments;
        $postComments = Post::find(1)->comments;

        // Récupère l'ID du 1er commentaire de chaque
        $userCommentId = $userComments[0]->id ?? null;
        $postCommentId = $postComments[0]->id ?? null;

        $this->assertNotSame($userCommentId, $postCommentId);
    }
}