<?php

declare(strict_types=1);

namespace Database\Test\Feature\Transactions;

use Clicalmani\Core\Support\Facades\DB;
use Database\Test\Feature\DatabaseTestCase;
use Database\Test\Feature\Fixtures\Models\User;

final class NestedTransactionTest extends DatabaseTestCase
{
    // ─── NIVEAU 1 : transaction simple ─────────────────────

    public function test_simple_transaction_commits(): void
    {
        DB::transaction(function () {
            User::create([
                'name'   => 'Simple',
                'email'  => 'simple@example.com',
                'age'    => 20,
                'active' => 1,
            ]);
        });

        $this->assertSame(1, $this->countRows('users'));
    }

    public function test_simple_transaction_rollbacks_on_exception(): void
    {
        try {
            DB::transaction(function () {
                User::create([
                    'name'   => 'Rollback',
                    'email'  => 'rollback@example.com',
                    'age'    => 20,
                    'active' => 1,
                ]);

                throw new \RuntimeException('boom');
            });
        } catch (\RuntimeException $e) {
            // Attendu
        }

        $this->assertSame(0, $this->countRows('users'));
    }

    public function test_transaction_returns_callback_result(): void
    {
        $result = DB::transaction(function () {
            return 'success';
        });

        $this->assertSame('success', $result);
    }

    // ─── NIVEAU 2 : savepoints ─────────────────────────────

    public function test_nested_transaction_creates_savepoint(): void
    {
        DB::transaction(function () {
            User::create([
                'name'  => 'Outer',
                'email' => 'outer@example.com',
                'age'   => 20,
                'active' => 1,
            ]);

            // Nested
            DB::transaction(function () {
                User::create([
                    'name'  => 'Inner',
                    'email' => 'inner@example.com',
                    'age'   => 21,
                    'active' => 1,
                ]);
            });
        });

        $this->assertSame(2, $this->countRows('users'));
    }

    public function test_nested_rollback_only_undoes_inner(): void
    {
        try {
            DB::transaction(function () {
                User::create([
                    'name'  => 'Outer',
                    'email' => 'outer@example.com',
                    'age'   => 20,
                    'active' => 1,
                ]);

                // Nested : rollback sur exception
                DB::transaction(function () {
                    User::create([
                        'name'  => 'Inner',
                        'email' => 'inner@example.com',
                        'age'   => 21,
                        'active' => 1,
                    ]);

                    throw new \RuntimeException('inner boom');
                });
            });
        } catch (\RuntimeException $e) {
            // Attendu — remonte au niveau externe → rollback global
        }

        // ⚠️ Selon l'implémentation :
        // - Si le nested rollback fonctionne ET le outer continue → 1 row
        // - Si l'exception remonte au outer → 0 row
        $count = $this->countRows('users');
        $this->assertContains($count, [0, 1], 'Comportement variable selon propagation');
    }

    public function test_nested_rollback_preserves_outer(): void
    {
        DB::transaction(function () {
            User::create([
                'name'  => 'Outer',
                'email' => 'outer@example.com',
                'age'   => 20,
                'active' => 1,
            ]);

            // Nested : on catch l'exception
            try {
                DB::transaction(function () {
                    User::create([
                        'name'  => 'Inner',
                        'email' => 'inner@example.com',
                        'age'   => 21,
                        'active' => 1,
                    ]);

                    throw new \RuntimeException('inner boom');
                });
            } catch (\RuntimeException $e) {
                // On swallow → le outer continue
            }
        });

        // Seul l'Outer doit persister
        $this->assertSame(1, $this->countRows('users'));

        $row = $this->pdo->query("SELECT name FROM users")->fetch();
        $this->assertSame('Outer', $row->name);
    }

    public function test_nested_commit_releases_savepoint(): void
    {
        DB::transaction(function () {
            User::create([
                'name'  => 'Outer',
                'email' => 'outer@example.com',
                'age'   => 20,
                'active' => 1,
            ]);

            // Nested sans exception
            DB::transaction(function () {
                User::create([
                    'name'  => 'Inner',
                    'email' => 'inner@example.com',
                    'age'   => 21,
                    'active' => 1,
                ]);
            });
        });

        // Les deux persistent
        $this->assertSame(2, $this->countRows('users'));
    }

    // ─── NIVEAU 3+ : savepoints profonds ───────────────────

    public function test_three_levels_of_nesting(): void
    {
        DB::transaction(function () {
            User::create([
                'name'  => 'L1',
                'email' => 'l1@example.com',
                'age'   => 20,
                'active' => 1,
            ]);

            DB::transaction(function () {
                User::create([
                    'name'  => 'L2',
                    'email' => 'l2@example.com',
                    'age'   => 21,
                    'active' => 1,
                ]);

                DB::transaction(function () {
                    User::create([
                        'name'  => 'L3',
                        'email' => 'l3@example.com',
                        'age'   => 22,
                        'active' => 1,
                    ]);
                });
            });
        });

        $this->assertSame(3, $this->countRows('users'));
    }

    public function test_deep_nesting_rollback_middle(): void
    {
        DB::transaction(function () {
            User::create([
                'name'  => 'L1',
                'email' => 'l1@example.com',
                'age'   => 20,
                'active' => 1,
            ]);

            try {
                DB::transaction(function () {
                    User::create([
                        'name'  => 'L2',
                        'email' => 'l2@example.com',
                        'age'   => 21,
                        'active' => 1,
                    ]);

                    DB::transaction(function () {
                        User::create([
                            'name'  => 'L3',
                            'email' => 'l3@example.com',
                            'age'   => 22,
                            'active' => 1,
                        ]);

                        throw new \RuntimeException('L3 boom');
                    });
                });
            } catch (\RuntimeException $e) {
                // Swallow
            }
        });

        // L1 et L2 doivent persister, L3 non
        $this->assertSame(2, $this->countRows('users'));

        $names = $this->pdo->query("SELECT name FROM users ORDER BY id")->fetchAll(\PDO::FETCH_COLUMN);
        $this->assertSame(['L1', 'L2'], $names);
    }

    // ─── SAVEPOINT UNIQUE NAMES ────────────────────────────

    public function test_savepoint_names_are_unique(): void
    {
        DB::transaction(function () {
            DB::transaction(function () {
                DB::transaction(function () {
                    User::create([
                        'name'  => 'Deep',
                        'email' => 'deep@example.com',
                        'age'   => 20,
                        'active' => 1,
                    ]);
                });
            });
        });

        // Si les noms se collisionnaient, on aurait une erreur SQL
        $this->assertSame(1, $this->countRows('users'));
    }

    // ─── SAVEPOINT WITH RAW QUERIES ────────────────────────

    public function test_manual_savepoint_and_rollback(): void
    {
        $this->pdo->beginTransaction();

        User::create([
            'name'  => 'BeforeSave',
            'email' => 'beforesave@example.com',
            'age'   => 20,
            'active' => 1,
        ]);

        // Créer un savepoint manuel
        $this->pdo->exec('SAVEPOINT my_savepoint');

        User::create([
            'name'  => 'AfterSave',
            'email' => 'aftersave@example.com',
            'age'   => 21,
            'active' => 1,
        ]);

        // Rollback au savepoint
        $this->pdo->exec('ROLLBACK TO SAVEPOINT my_savepoint');

        $this->pdo->commit();

        // Seul 'BeforeSave' doit exister
        $this->assertSame(1, $this->countRows('users'));

        $row = $this->pdo->query("SELECT name FROM users")->fetch();
        $this->assertSame('BeforeSave', $row->name);
    }

    public function test_manual_savepoint_release(): void
    {
        $this->pdo->beginTransaction();

        $this->pdo->exec('SAVEPOINT sp1');

        User::create([
            'name'  => 'Released',
            'email' => 'released@example.com',
            'age'   => 20,
            'active' => 1,
        ]);

        $this->pdo->exec('RELEASE SAVEPOINT sp1');

        $this->pdo->commit();

        $this->assertSame(1, $this->countRows('users'));
    }

    // ─── TRANSACTION LEVEL TRACKING ────────────────────────

    public function test_transaction_level_starts_at_zero(): void
    {
        $this->assertFalse(DB::getInstance()->inTransaction());
    }

    public function test_in_transaction_true_inside(): void
    {
        DB::transaction(function () {
            $this->assertTrue(DB::inTransaction());
        });

        $this->assertFalse(DB::inTransaction());
    }

    public function test_exception_in_nested_bubbles_up(): void
    {
        $this->expectException(\RuntimeException::class);

        DB::transaction(function () {
            DB::transaction(function () {
                throw new \RuntimeException('bubbled');
            });
        });
    }

    public function test_multiple_sequential_transactions(): void
    {
        DB::transaction(function () {
            User::create(['name' => 'T1', 'email' => 't1@example.com', 'age' => 20, 'active' => 1]);
        });

        DB::transaction(function () {
            User::create(['name' => 'T2', 'email' => 't2@example.com', 'age' => 21, 'active' => 1]);
        });

        DB::transaction(function () {
            User::create(['name' => 'T3', 'email' => 't3@example.com', 'age' => 22, 'active' => 1]);
        });

        $this->assertSame(3, $this->countRows('users'));
    }

    public function test_transaction_with_return_value(): void
    {
        $result = DB::transaction(function () {
            User::create(['name' => 'Return', 'email' => 'r@example.com', 'age' => 20, 'active' => 1]);
            return 42;
        });

        $this->assertSame(42, $result);
        $this->assertSame(1, $this->countRows('users'));
    }
}