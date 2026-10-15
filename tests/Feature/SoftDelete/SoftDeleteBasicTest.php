<?php

declare(strict_types=1);

namespace Database\Test\Feature\SoftDelete;

use Database\Test\Feature\DatabaseTestCase;
use Database\Test\Feature\Fixtures\Models\SoftUser;

final class SoftDeleteBasicTest extends DatabaseTestCase
{
    // ─── TRASHED ───────────────────────────────────────────

    public function test_fresh_model_is_not_trashed(): void
    {
        $this->seedDatabase();

        $user = SoftUser::find(1);

        $this->assertFalse($user->trashed());
    }

    public function test_trashed_model_returns_true(): void
    {
        $this->seedDatabase();

        $user = SoftUser::find(1);
        $user->delete();

        // Récupère avec trashed
        $trashed = SoftUser::withTrashed()->find(1);

        $this->assertTrue($trashed->trashed());
    }

    // ─── SOFT DELETE ───────────────────────────────────────

    public function test_delete_marks_row_as_trashed(): void
    {
        $this->seedDatabase();

        $user = SoftUser::find(1);
        $success = $user->delete();

        $this->assertTrue($success);

        // Vérifier en base
        $row = $this->pdo->query('SELECT deleted_at FROM soft_users WHERE id = 1')->fetch(\PDO::FETCH_OBJ);
        $this->assertNotNull($row->deleted_at);
    }

    public function test_delete_does_not_remove_row_from_database(): void
    {
        $this->seedDatabase();

        SoftUser::find(1)->delete();

        // La ligne existe toujours
        $this->assertSame(5, $this->countRows('soft_users'));
    }

    public function test_delete_sets_deleted_at_column(): void
    {
        $this->seedDatabase();

        $before = date('Y-m-d H:i:s');
        SoftUser::find(1)->delete();
        $after = date('Y-m-d H:i:s');

        $row = $this->pdo->query('SELECT deleted_at FROM soft_users WHERE id = 1')->fetch(\PDO::FETCH_OBJ);

        $this->assertNotNull($row->deleted_at);
        $this->assertGreaterThanOrEqual($before, $row->deleted_at);
    }

    // ─── SOFT DELETE VIA QUERY ─────────────────────────────

    public function test_bulk_soft_delete(): void
    {
        $this->seedDatabase();

        SoftUser::where('id IN (1, 2, 3)')->delete();

        // Vérifier que les 3 sont soft-deleted
        $count = (int) $this->pdo->query(
            'SELECT COUNT(*) FROM soft_users WHERE deleted_at IS NOT NULL'
        )->fetchColumn();

        $this->assertSame(3, $count);
    }

    public function test_soft_delete_on_unsaved_model_throws(): void
    {
        $user = new SoftUser;
        $user->name = 'Unsaved';

        $this->expectException(\Clicalmani\Core\Exceptions\ModelException::class);

        $user->delete();
    }
}