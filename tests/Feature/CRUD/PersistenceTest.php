<?php

declare(strict_types=1);

namespace Database\Test\Feature\CRUD;

use Clicalmani\Core\Support\Facades\DB;
use Database\Test\Feature\DatabaseTestCase;
use Database\Test\Feature\Fixtures\Models\User;

final class PersistenceTest extends DatabaseTestCase
{
    /**
     * Cycle complet : Create → Read → Update → Delete.
     */
    public function test_full_lifecycle(): void
    {
        // ─── CREATE ────────────────────────────────────────
        $user = User::create([
            'name'   => 'Lifecycle',
            'email'  => 'lifecycle@example.com',
            'age'    => 30,
            'active' => 1,
        ]);

        $this->assertNotNull($user->id);
        $this->assertSame(1, $this->countRows('users'));

        // ─── READ ──────────────────────────────────────────
        $found = User::find($user->id);
        $this->assertSame('Lifecycle', $found->name);

        // ─── UPDATE ────────────────────────────────────────
        $found->name = 'Updated';
        $found->save();

        $updated = User::find($user->id);
        $this->assertSame('Updated', $updated->name);

        // ─── DELETE ────────────────────────────────────────
        $deleted = $updated->delete();
        $this->assertTrue($deleted);
        $this->assertSame(0, $this->countRows('users'));
    }

    public function test_multiple_lifecycles(): void
    {
        // Créer 3 users
        for ($i = 1; $i <= 3; $i++) {
            User::create([
                'name'  => "User $i",
                'email' => "u$i@example.com",
                'age'   => 20 + $i,
                'active' => 1,
            ]);
        }

        $this->assertSame(3, $this->countRows('users'));

        // Modifier tous les users actifs
        User::where('active = ?', [1])->update(['name' => 'Updated']);

        $updated = User::where('name = ?', ['Updated'])->get();
        $this->assertSame(3, $updated->count());

        // Supprimer 1 user
        User::find(2)->delete();
        $this->assertSame(2, $this->countRows('users'));
    }

    public function test_save_then_reload(): void
    {
        $user = User::create([
            'name'  => 'Original',
            'email' => 'orig@example.com',
            'age'   => 25,
            'active' => 1,
        ]);

        $id = (int) $user->id;

        $user->name = 'Modified';
        $user->save();

        $fresh = User::find($id);
        $this->assertSame('Modified', $fresh->name);
    }

    public function test_update_single_field_preserves_others(): void
    {
        $user = User::create([
            'name'   => 'Alice',
            'email'  => 'alice@example.com',
            'age'    => 30,
            'active' => 1,
        ]);

        $id = (int) $user->id;

        $user->name = 'Alicia';
        $user->save();

        $fresh = User::find($id);
        $this->assertSame('Alicia', $fresh->name);
        $this->assertSame('alice@example.com', $fresh->email);
        $this->assertSame(30, (int) $fresh->age);
    }

    public function test_transaction_rollback_undoes_changes(): void
    {
        try {
            DB::transaction(function () {
                User::create([
                    'name'  => 'Rollback',
                    'email' => 'rb@example.com',
                    'age'   => 20,
                    'active' => 1,
                ]);

                throw new \RuntimeException('rollback');
            });
        } catch (\RuntimeException $e) {
            // Attendu
        }

        $this->assertSame(0, $this->countRows('users'));
    }

    public function test_transaction_commit_persists_changes(): void
    {
        DB::transaction(function () {
            User::create([
                'name'  => 'Committed',
                'email' => 'commit@example.com',
                'age'   => 25,
                'active' => 1,
            ]);
        });

        $this->assertSame(1, $this->countRows('users'));
    }

    public function test_concurrent_updates_last_wins(): void
    {
        $user = User::create([
            'name' => 'Original',
            'email' => 'concurrent@example.com',
            'age' => 20,
            'active' => 1,
        ]);

        $id = (int) $user->id;

        // Deux instances du même user
        $a = User::find($id);
        $b = User::find($id);

        // Les deux modifient + save
        $a->name = 'From A';
        $a->save();

        $b->name = 'From B';
        $b->save();

        // Le dernier gagne
        $final = User::find($id);
        $this->assertSame('From B', $final->name);
    }

    public function test_row_count_after_multiple_operations(): void
    {
        User::create(['name' => 'A', 'email' => 'a@x.com', 'age' => 20, 'active' => 1]);
        User::create(['name' => 'B', 'email' => 'b@x.com', 'age' => 21, 'active' => 1]);
        User::create(['name' => 'C', 'email' => 'c@x.com', 'age' => 22, 'active' => 1]);

        $this->assertSame(3, $this->countRows('users'));

        User::where('name = ?', ['B'])->delete();

        $this->assertSame(2, $this->countRows('users'));
    }
}