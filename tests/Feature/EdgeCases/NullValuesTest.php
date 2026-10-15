<?php

declare(strict_types=1);

namespace Database\Test\Feature\EdgeCases;

use Database\Test\Feature\DatabaseTestCase;
use Database\Test\Feature\Fixtures\Models\User;

final class NullValuesTest extends DatabaseTestCase
{
    public function test_insert_null_into_nullable_column(): void
    {
        $user = new User;
        $user->name  = 'Nullable';
        $user->email = null;    // ← nullable
        $user->age   = 20;
        $user->save();

        $row = $this->pdo->query("SELECT email FROM users WHERE name = 'Nullable'")->fetch(\PDO::FETCH_OBJ);
        $this->assertNull($row->email);
    }

    public function test_insert_null_into_not_null_column_throws(): void
    {
        $this->expectException(\PDOException::class);

        $user = new User;
        $user->name  = null;    // ← NOT NULL
        $user->email = 'x@example.com';
        $user->age   = 20;
        $user->save();
    }

    public function test_where_null_returns_rows(): void
    {
        $this->seedDatabase();

        $user = new User;
        $user->name  = 'NullEmail';
        $user->email = null;
        $user->age   = 20;
        $user->save();

        $found = User::where('email IS NULL')->get();

        $this->assertGreaterThan(0, $found->count());
    }

    public function test_where_not_null(): void
    {
        $this->seedDatabase();

        $found = User::where('email IS NOT NULL')->get();

        $this->assertSame(5, $found->count());
    }

    public function test_update_to_null(): void
    {
        $this->seedDatabase();

        $user = User::find(1);
        $user->email = null;
        $user->save();

        $row = $this->pdo->query('SELECT email FROM users WHERE id = 1')->fetch(\PDO::FETCH_OBJ);
        $this->assertNull($row->email);
    }

    public function test_find_with_null_id_returns_null(): void
    {
        $this->seedDatabase();

        $this->assertNull(User::find(null));
    }

    public function test_where_with_null_binding(): void
    {
        $this->seedDatabase();

        // SQL : WHERE email = ? avec binding null → ne matche rien (SQL standard)
        $result = User::where('email = ?', [null])->get();

        $this->assertSame(0, $result->count());
    }
}