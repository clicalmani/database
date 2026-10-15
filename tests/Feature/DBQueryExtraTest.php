<?php

declare(strict_types=1);

namespace Database\Test\Feature;

use Clicalmani\Core\Support\Facades\DB;
use Clicalmani\Database\DBQuery;

final class DBQueryExtraTest extends DatabaseTestCase
{
    private function users(): DBQuery
    {
        return DB::table('users');
    }

    // ─── FINDO ─────────────────────────────────────────────

    public function test_find_returns_row_by_id(): void
    {
        $this->seedDatabase();

        $user = $this->users()->find(3);

        $this->assertNotNull($user);
        $this->assertSame(3, (int) $user->id);
        $this->assertSame('User 3', $user->name);
    }

    public function test_find_returns_null_when_no_match(): void
    {
        $this->seedDatabase();

        $this->assertNull($this->users()->find(999));
    }

    public function test_find_with_custom_column(): void
    {
        $this->seedDatabase();

        $user = $this->users()->find('user2@example.com', 'email');

        $this->assertNotNull($user);
        $this->assertSame('User 2', $user->name);
    }

    // ─── FIRST OR FAIL ─────────────────────────────────────

    public function test_first_or_fail_returns_row(): void
    {
        $this->seedDatabase();

        $user = $this->users()->where('id = ?', [1])->firstOrFail();

        $this->assertNotNull($user);
        $this->assertSame('User 1', $user->name);
    }

    public function test_first_or_fail_throws_when_no_match(): void
    {
        $this->seedDatabase();

        $this->expectException(\Exception::class);

        $this->users()->where('id = ?', [999])->firstOrFail();
    }

    // ─── UPDATE / DELETE ───────────────────────────────────

    public function test_update_returns_affected_rows(): void
    {
        $this->seedDatabase();

        $affected = $this->users()->where('id = ?', [1])->update(['name' => 'Updated']);

        $this->assertSame(1, $affected);

        $name = $this->pdo->query('SELECT name FROM users WHERE id = 1')->fetchColumn();
        $this->assertSame('Updated', $name);
    }

    public function test_increment(): void
    {
        $this->seedDatabase();

        $this->users()->where('id = ?', [1])->increment('age', 5);

        $age = (int) $this->pdo->query('SELECT age FROM users WHERE id = 1')->fetchColumn();
        $this->assertSame(26, $age); // 21 + 5
    }

    public function test_decrement(): void
    {
        $this->seedDatabase();

        $this->users()->where('id = ?', [1])->decrement('age', 1);

        $age = (int) $this->pdo->query('SELECT age FROM users WHERE id = 1')->fetchColumn();
        $this->assertSame(20, $age); // 21 - 1
    }

    // ─── UNION ─────────────────────────────────────────────

    public function test_union_of_two_queries(): void
    {
        $this->seedDatabase();

        $q1 = $this->users()->where('id = ?', [1]);
        $q2 = $this->users()->where('id = ?', [2]);

        // L'API union() est sur Elegant, pas sur DBQuery directement.
        // On teste juste que ça ne casse pas si supporté.
        if (method_exists($q1, 'union')) {
            $result = $q1->union($q2)->get();
            $this->assertGreaterThanOrEqual(2, $result->count());
        } else {
            $this->markTestSkipped('union() non supporté par DBQuery');
        }
    }

    // ─── WHERE EXISTS ──────────────────────────────────────

    public function test_where_exists_with_subquery(): void
    {
        $this->seedDatabase();

        $result = $this->users()->whereExists(
            'SELECT 1 FROM posts WHERE posts.user_id = users.id'
        )->get();

        // Tous les users ont au moins 1 post
        $this->assertSame(5, $result->count());
    }

    public function test_where_not_exists_with_subquery(): void
    {
        $this->seedDatabase();

        $result = $this->users()->whereNotExists(
            'SELECT 1 FROM posts WHERE posts.user_id = users.id'
        )->get();

        // Aucun user sans post
        $this->assertSame(0, $result->count());
    }

    // ─── WHERE HASS / DOESNT HAVE ─────────────────────────

    public function test_where_has_returns_parents_with_children(): void
    {
        $this->seedDatabase();

        $result = $this->users()->whereHas('posts', function ($q) {
            // Le callback est exécuté sur une DBSubQuery
        })->get();

        // Le comportement exact dépend de DBSubQuery
        $this->assertGreaterThan(0, $result->count());
    }

    // ─── WHERE RAW ─────────────────────────────────────────

    public function test_where_raw(): void
    {
        $this->seedDatabase();

        $result = $this->users()->whereRaw('age >= 23')->get();

        $this->assertSame(3, $result->count()); // 23, 24, 25
    }

    // ─── ORDER BY RAW ──────────────────────────────────────

    public function test_order_by_raw(): void
    {
        $this->seedDatabase();

        $result = $this->users()->orderByRaw('age DESC')->get();
        $ages = array_map(fn($u) => $u->age, $result->toArray());

        $this->assertSame([25, 24, 23, 22, 21], $ages);
    }

    // ─── GET PARAMS / OPTIONS ─────────────────────────────

    public function test_get_params_returns_array(): void
    {
        $params = $this->users()->getParams();

        $this->assertIsArray($params);
        $this->assertArrayHasKey('tables', $params);
    }

    public function test_get_options_returns_array_or_null(): void
    {
        $options = $this->users()->getOptions();

        $this->assertTrue(is_array($options) || is_null($options));
    }

    public function test_set_and_get_param(): void
    {
        $q = $this->users();
        $q->set('custom', 'value');

        $this->assertSame('value', $q->getParam('custom'));
    }

    public function test_unset_param(): void
    {
        $q = $this->users();
        $q->set('custom', 'value');
        $q->unset('custom');

        $this->assertNull($q->getParam('custom'));
    }

    // ─── WITH ──────────────────────────────────────────────

    public function test_with_registers_relations(): void
    {
        $this->seedDatabase();

        $q = $this->users()->with('posts');

        // Vérifie juste que la méthode existe et ne casse pas
        $this->assertInstanceOf(DBQuery::class, $q);
    }

    // ─── LAZY BY / LAZY BY DESC ───────────────────────────

    public function test_lazy_by(): void
    {
        $this->seedDatabase();

        $ids = [];
        foreach ($this->users()->lazyBy('id', 2) as $user) {
            $ids[] = $user->id;
        }

        $this->assertCount(5, $ids);
    }

    public function test_lazy_by_desc(): void
    {
        $this->seedDatabase();

        $ids = [];
        foreach ($this->users()->lazyByDesc('id', 2) as $user) {
            $ids[] = $user->id;
        }

        $this->assertCount(5, $ids);
        $this->assertSame([5, 4, 3, 2, 1], $ids);
    }

    // ─── CHUNK BY ID ───────────────────────────────────────

    public function test_chunk_by_id(): void
    {
        $this->seedDatabase();

        $collected = [];
        $this->users()->chunkById(2, function ($rows) use (&$collected) {
            foreach ($rows as $row) $collected[] = $row->id;
        });

        $this->assertCount(5, $collected);
    }

    // ─── UNLESS ────────────────────────────────────────────

    public function test_unless_condition_true(): void
    {
        $this->seedDatabase();

        $result = $this->users()->unless(true, fn($q) => $q->where('id = ?', [1]))->get();

        // Condition true → callback pas exécuté → tous les users
        $this->assertCount(5, $result);
    }

    public function test_unless_condition_false(): void
    {
        $this->seedDatabase();

        $result = $this->users()->unless(false, fn($q) => $q->where('id = ?', [1]))->get();

        // Condition false → callback exécuté → 1 user
        $this->assertCount(1, $result);
    }

    // ─── REPLACE ───────────────────────────────────────────

    public function test_replace_inserts_new_row(): void
    {
        $this->seedDatabase();

        $stmt = $this->users()->replace([
            'id'    => 100,
            'name'  => 'Replaced',
            'email' => 'replaced@example.com',
            'age'   => 50,
            'active' => 1,
        ]);
        $stmt->query();

        $this->assertSame('success', $stmt->status());
        $this->assertSame(6, $this->countRows('users'));
    }

    // ─── JOIN CROSS ────────────────────────────────────────

    public function test_join_cross(): void
    {
        $this->seedDatabase();

        $result = $this->users()->joinCross('roles')->get();

        // 5 users × 2 roles = 10
        $this->assertSame(10, $result->count());
    }

    // ─── JOIN RIGHT ────────────────────────────────────────

    public function test_join_right(): void
    {
        $this->seedDatabase();

        $result = $this->users()->joinRight('posts', 'posts.user_id', 'users.id')->get();

        $this->assertSame(15, $result->count());
    }

    // ─── MARKER ────────────────────────────────────────────

    public function test_marker_changes_placeholder_style(): void
    {
        $q = $this->users()->marker('?');

        $this->assertSame('?', $q->getParam('marker'));
    }

    // ─── FROM ──────────────────────────────────────────────

    public function test_from_overrides_table(): void
    {
        $this->seedDatabase();

        // On part de "users" mais on remplace par "roles"
        $result = $this->users()->from('roles')->get();

        $this->assertCount(2, $result);
    }

    // ─── GLOBAL ────────────────────────────────────────────

    public function test_raw_method(): void
    {
        $raw = DB::table('users')->raw('COUNT(*)');

        $this->assertSame('COUNT(*)', $raw->getValue());
    }

    public function test_insert_returns_insert_statement(): void
    {
        $stmt = $this->users()->insert(['name' => 'Test']);

        $this->assertInstanceOf(
            \Clicalmani\Database\Builders\AbstractInsertStatement::class,
            $stmt
        );
    }

    public function test_delete_returns_bool(): void
    {
        $this->seedDatabase();

        $result = $this->users()->where('id = ?', [1])->delete();

        $this->assertIsBool($result);
    }

    public function test_truncate_returns_bool(): void
    {
        $this->seedDatabase();
        $this->pdo->query('SET FOREIGN_KEY_CHECKS = 0');

        $result = $this->users()->truncate();
        
        $this->assertIsBool($result);
    }
}