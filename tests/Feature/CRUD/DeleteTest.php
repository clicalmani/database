<?php

declare(strict_types=1);

namespace Database\Test\Feature\CRUD;

use Clicalmani\Core\Support\Facades\DB;
use Database\Test\Feature\DatabaseTestCase;
use Database\Test\Feature\Fixtures\Models\User;

final class DeleteTest extends DatabaseTestCase
{
    // ─── DELETE METHOD ─────────────────────────────────────

    public function test_delete_removes_row(): void
    {
        $this->seedDatabase();

        $user = User::find(1);
        $success = $user->delete();

        $this->assertTrue($success);
        $this->assertSame(4, $this->countRows('users'));
    }

    public function test_delete_returns_bool(): void
    {
        $this->seedDatabase();

        $user = User::find(1);
        $result = $user->delete();

        $this->assertIsBool($result);
    }

    public function test_deleted_row_is_not_findable(): void
    {
        $this->seedDatabase();

        User::find(1)->delete();

        $this->assertNull(User::find(1));
    }

    // ─── BULK DELETE ───────────────────────────────────────

    public function test_bulk_delete_via_query(): void
    {
        $this->seedDatabase();

        $success = User::where('active = ?', [1])->delete();

        $this->assertTrue($success);
        $this->assertSame(2, $this->countRows('users'));
    }

    public function test_delete_with_in(): void
    {
        $this->seedDatabase();

        User::whereIn('id', [1, 2, 3])->delete();

        $this->assertSame(2, $this->countRows('users'));
    }

    // ─── CASCADE ───────────────────────────────────────────

    public function test_delete_cascades_to_posts(): void
    {
        $this->seedDatabase();

        // 5 users × 3 posts = 15
        $this->assertSame(15, $this->countRows('posts'));

        User::find(1)->delete();

        // User 1 avait 3 posts → 12 restants
        $this->assertSame(12, $this->countRows('posts'));
    }

    public function test_delete_user_with_posts_via_cascade(): void
    {
        $this->seedDatabase();

        User::whereIn('id', [1, 2])->delete();

        // 2 users × 3 posts = 6 posts supprimés → 9 restants
        $this->assertSame(9, $this->countRows('posts'));
    }

    // ─── TRUNCATE ──────────────────────────────────────────

    public function test_truncate_empties_table(): void
    {
        $this->seedDatabase();
        $this->pdo->query('SET FOREIGN_KEY_CHECKS = 0');

        User::truncate();

        $this->assertSame(0, $this->countRows('users'));
    }

    // ─── FORCE DELETE ──────────────────────────────────────

    public function test_force_delete_with_where(): void
    {
        $this->seedDatabase();

        $user = User::where('id = ?', [1]);

        $success = $user->forceDelete();

        $this->assertTrue($success);
        $this->assertSame(4, $this->countRows('users'));
    }

    public function test_force_delete_without_where_throws(): void
    {
        $this->seedDatabase();

        $this->expectException(\Error::class);

        User::forceDelete();
    }
}