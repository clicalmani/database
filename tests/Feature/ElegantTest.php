<?php

declare(strict_types=1);

namespace Database\Test\Feature;

use Database\Test\Feature\Fixtures\Models\Post;
use Database\Test\Feature\Fixtures\Models\User;

final class ElegantTest extends DatabaseTestCase
{
    // ─── FIND / FIRST ──────────────────────────────────────

    public function test_find_returns_model(): void
    {
        $this->seedDatabase();

        $user = User::find(1);

        $this->assertInstanceOf(User::class, $user);
        $this->assertSame(1, (int) $user->id);
        $this->assertSame('User 1', $user->name);
    }

    public function test_find_returns_null_when_no_match(): void
    {
        $this->seedDatabase();

        $this->assertNull(User::find(999));
    }

    public function test_first_returns_model(): void
    {
        $this->seedDatabase();

        $user = User::where('id = ?', [1])->first();

        $this->assertInstanceOf(User::class, $user);
    }

    public function test_first_returns_null_when_empty(): void
    {
        $this->seedDatabase();

        $this->assertNull(User::where('id = ?', [999])->first());
    }

    // ─── GET ───────────────────────────────────────────────

    public function test_get_returns_collection_of_models(): void
    {
        $this->seedDatabase();

        $users = User::where('active = ?', [1])->get();

        $this->assertGreaterThan(0, $users->count());
        $this->assertInstanceOf(User::class, $users->first());
    }

    public function test_all_returns_collection(): void
    {
        $this->seedDatabase();

        $users = User::all();

        $this->assertSame(5, $users->count());
    }

    // ─── UPDATE ────────────────────────────────────────────

    public function test_update_method_returns_bool(): void
    {
        $this->seedDatabase();

        $user = User::find(1);
        $success = $user->update(['name' => 'Renamed']);

        $this->assertTrue($success);
        $name = $this->pdo->query('SELECT name FROM users WHERE id = 1')->fetchColumn();
        $this->assertSame('Renamed', $name);
    }

    // ─── DELETE ────────────────────────────────────────────

    public function test_delete_removes_record(): void
    {
        $this->seedDatabase();

        $user = User::find(1);
        $success = $user->delete();

        $this->assertTrue($success);
        $this->assertSame(4, $this->countRows('users'));
    }

    // ─── RELATIONS ─────────────────────────────────────────

    public function test_belongs_to_via_magic_property(): void
    {
        $this->seedDatabase();

        $post = Post::find(1);
        $user = $post->user;

        $this->assertInstanceOf(User::class, $user);
        $this->assertSame((int) $post->user_id, (int) $user->id);
    }

    public function test_has_many_via_magic_property(): void
    {
        $this->seedDatabase();

        $user = User::find(1);
        $posts = $user->posts;

        $this->assertGreaterThanOrEqual(1, count($posts));
    }

    // ─── AGGREGATES ────────────────────────────────────────

    public function test_count(): void
    {
        $this->seedDatabase();

        $this->assertSame(5, User::count());
    }

    public function test_sum(): void
    {
        $this->seedDatabase();

        $this->assertSame(115, User::sum('age'));
    }

    public function test_avg(): void
    {
        $this->seedDatabase();

        $this->assertSame(23.0, User::avg('age'));
    }

    public function test_min(): void
    {
        $this->seedDatabase();

        $this->assertSame(21, User::min('age'));
    }

    public function test_max(): void
    {
        $this->seedDatabase();

        $this->assertSame(25, User::max('age'));
    }

    // ─── CHUNK / LAZY ──────────────────────────────────────

    public function test_chunk_yields_models(): void
    {
        $this->seedDatabase();

        $collected = [];
        User::where('id > ?', [0])->chunk(2, function ($users) use (&$collected) {
            foreach ($users as $u) $collected[] = (int) $u->id;
        });

        $this->assertCount(5, $collected);
    }

    public function test_lazy_yields_models(): void
    {
        $this->seedDatabase();

        $ids = [];
        foreach (User::where('id > ?', [0])->lazy(chunk: 2) as $user) {
            $ids[] = (int) $user->id;
        }

        $this->assertCount(5, $ids);
    }
}