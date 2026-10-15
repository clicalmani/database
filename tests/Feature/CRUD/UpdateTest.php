<?php

declare(strict_types=1);

namespace Database\Test\Feature\CRUD;

use PDO;
use Database\Test\Feature\DatabaseTestCase;
use Database\Test\Feature\Fixtures\Models\User;

final class UpdateTest extends DatabaseTestCase
{
    // ─── UPDATE VIA ATTRIBUTS + SAVE ───────────────────────

    public function test_update_via_save(): void
    {
        $this->seedDatabase();

        $user = User::find(1);
        $user->name = 'Renamed';
        $success = $user->save();

        $this->assertTrue($success);

        $row = $this->pdo->query('SELECT name FROM users WHERE id = 1')->fetch(PDO::FETCH_OBJ);
        $this->assertSame('Renamed', $row->name);
    }

    public function test_update_persists_multiple_attributes(): void
    {
        $this->seedDatabase();

        $user = User::find(1);
        $user->name   = 'Multi';
        $user->age    = 99;
        $user->active = 0;
        $user->save();

        $row = $this->pdo->query('SELECT * FROM users WHERE id = 1')->fetch(PDO::FETCH_OBJ);
        $this->assertSame('Multi', $row->name);
        $this->assertSame(99, (int) $row->age);
        $this->assertSame(0, (int) $row->active);
    }

    // ─── UPDATE METHOD ─────────────────────────────────────

    public function test_update_method_returns_true(): void
    {
        $this->seedDatabase();

        $user = User::find(1);
        $affected = $user->update(['name' => 'Updated']);

        $this->assertEquals(1, $affected);
    }

    public function test_update_method_persists(): void
    {
        $this->seedDatabase();

        User::find(1)->update(['name' => 'Persisted']);

        $row = $this->pdo->query('SELECT name FROM users WHERE id = 1')->fetch(PDO::FETCH_OBJ);
        $this->assertSame('Persisted', $row->name);
    }

    public function test_update_empty_array_returns_false(): void
    {
        $this->seedDatabase();

        $user = User::find(1);
        $affected = $user->update([]);

        $this->assertEquals(0, $affected);
    }

    // ─── BULK UPDATE VIA QUERY ─────────────────────────────

    public function test_bulk_update_via_query(): void
    {
        $this->seedDatabase();

        $affected = User::where('active = ?', [1])->update(['active' => 0]);

        $this->assertSame(3, $affected);

        $count = (int) $this->pdo->query('SELECT COUNT(*) FROM users WHERE active = 1')->fetchColumn();
        $this->assertSame(0, $count);
    }

    // ─── INCREMENT / DECREMENT ─────────────────────────────

    public function test_increment(): void
    {
        $this->seedDatabase();

        User::where('id = ?', [1])->increment('age', 5);

        $age = (int) $this->pdo->query('SELECT age FROM users WHERE id = 1')->fetchColumn();
        $this->assertSame(26, $age);
    }

    public function test_decrement(): void
    {
        $this->seedDatabase();

        User::where('id = ?', [1])->decrement('age', 1);

        $age = (int) $this->pdo->query('SELECT age FROM users WHERE id = 1')->fetchColumn();
        $this->assertSame(20, $age);
    }

    // ─── DIRTY TRACKING ────────────────────────────────────

    public function test_is_dirty_after_modification(): void
    {
        $this->seedDatabase();

        $user = User::find(1);
        $user->name = 'Dirty';

        $this->assertTrue($user->isDirty('name'));
    }

    public function test_is_clean_without_modification(): void
    {
        $this->seedDatabase();

        $user = User::find(1);

        $this->assertFalse($user->isDirty('name'));
    }

    public function test_get_dirty_returns_modified_attributes(): void
    {
        $this->seedDatabase();

        $user = User::find(1);
        $user->name = 'Dirty';

        $dirty = $user->getDirty();

        $this->assertIsArray($dirty);
    }

    // ─── RELOAD ────────────────────────────────────────────

    public function test_save_then_reload_sees_changes(): void
    {
        $this->seedDatabase();

        $user = User::find(1);
        $user->name = 'Reload';
        $user->save();

        // Nouvelle requête → doit voir le changement
        $fresh = User::find(1);
        $this->assertSame('Reload', $fresh->name);
    }
}