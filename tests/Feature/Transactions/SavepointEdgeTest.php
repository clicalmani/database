<?php

declare(strict_types=1);

namespace Database\Test\Feature\Transactions;

use Clicalmani\Core\Support\Facades\DB;
use Database\Test\Feature\DatabaseTestCase;
use Database\Test\Feature\Fixtures\Models\User;

final class SavepointEdgeTest extends DatabaseTestCase
{
    public function test_savepoint_with_no_outer_transaction(): void
    {
        // Un SAVEPOINT sans transaction → erreur SQLite/MySQL
        $this->expectException(\PDOException::class);

        $this->pdo->exec('SAVEPOINT orphan_sp');
    }

    public function test_rollback_to_nonexistent_savepoint_throws(): void
    {
        $this->expectException(\PDOException::class);

        $this->pdo->beginTransaction();
        $this->pdo->exec('ROLLBACK TO SAVEPOINT does_not_exist');
        $this->pdo->rollBack();
    }

    public function test_release_nonexistent_savepoint_throws(): void
    {
        $this->expectException(\PDOException::class);

        $this->pdo->beginTransaction();
        $this->pdo->exec('RELEASE SAVEPOINT does_not_exist');
        $this->pdo->rollBack();
    }

    public function test_nested_transactions_preserve_data_integrity(): void
    {
        $db = DB::getInstance();

        // 3 niveaux, on rollback le milieu
        $db->transaction(function () use ($db) {
            User::create(['name' => 'A', 'email' => 'a@example.com', 'age' => 20, 'active' => 1]);

            try {
                $db->transaction(function () use ($db) {
                    User::create(['name' => 'B', 'email' => 'b@example.com', 'age' => 21, 'active' => 1]);

                    $db->transaction(function () {
                        User::create(['name' => 'C', 'email' => 'c@example.com', 'age' => 22, 'active' => 1]);
                        throw new \RuntimeException('C boom');
                    });
                });
            } catch (\RuntimeException $e) {
                // Swallow
            }

            User::create(['name' => 'D', 'email' => 'd@example.com', 'age' => 23, 'active' => 1]);
        });

        // A, B, D doivent persister. C non.
        $this->assertSame(3, $this->countRows('users'));

        $names = $this->pdo->query("SELECT name FROM users ORDER BY id")->fetchAll(\PDO::FETCH_COLUMN);
        $this->assertSame(['A', 'B', 'D'], $names);
    }

    public function test_transaction_after_rollback_is_clean(): void
    {
        $db = DB::getInstance();

        try {
            $db->transaction(function () {
                User::create(['name' => 'X', 'email' => 'x@example.com', 'age' => 20, 'active' => 1]);
                throw new \RuntimeException('boom');
            });
        } catch (\RuntimeException $e) {
            // Ignore
        }

        $this->assertFalse($db->inTransaction());

        // Nouvelle transaction doit fonctionner
        $db->transaction(function () {
            User::create(['name' => 'Y', 'email' => 'y@example.com', 'age' => 21, 'active' => 1]);
        });

        $this->assertSame(1, $this->countRows('users'));
    }

    public function test_consecutive_savepoints_with_different_data(): void
    {
        $db = DB::getInstance();

        $db->transaction(function () use ($db) {
            try {
                $db->transaction(function () {
                    User::create(['name' => 'Fail1', 'email' => 'fail1@example.com', 'age' => 20, 'active' => 1]);
                    throw new \RuntimeException('fail 1');
                });
            } catch (\RuntimeException $e) {
                // Ignore
            }

            try {
                $db->transaction(function () {
                    User::create(['name' => 'Fail2', 'email' => 'fail2@example.com', 'age' => 21, 'active' => 1]);
                    throw new \RuntimeException('fail 2');
                });
            } catch (\RuntimeException $e) {
                // Ignore
            }

            $db->transaction(function () {
                User::create(['name' => 'Success', 'email' => 'success@example.com', 'age' => 22, 'active' => 1]);
            });
        });

        $this->assertSame(1, $this->countRows('users'));

        $row = $this->pdo->query("SELECT name FROM users")->fetch();
        $this->assertSame('Success', $row->name);
    }

    public function test_savepoint_does_not_leak_to_next_transaction(): void
    {
        $db = DB::getInstance();

        $db->transaction(function () use ($db) {
            $db->transaction(function () {
                User::create(['name' => 'Sp1', 'email' => 'sp1@example.com', 'age' => 20, 'active' => 1]);
            });
        });

        // Nouvelle transaction indépendante
        $db->transaction(function () use ($db) {
            $db->transaction(function () {
                User::create(['name' => 'Sp2', 'email' => 'sp2@example.com', 'age' => 21, 'active' => 1]);
            });
        });

        $this->assertSame(2, $this->countRows('users'));
    }
}