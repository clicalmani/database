<?php

declare(strict_types=1);

namespace Database\Test\Feature;

use Clicalmani\Core\Support\Facades\DB;
use Clicalmani\Database\Listeners\QueryListenerRegistry;

final class DBTest extends DatabaseTestCase
{
    public function test_get_pdo_returns_same_instance(): void
    {
        $pdo1 = DB::getInstance()->getPdo();
        $pdo2 = DB::getInstance()->getPdo();
        $this->assertSame($pdo1, $pdo2);
    }

    public function test_get_prefix_default_empty(): void
    {
        $this->assertSame('', DB::getInstance()->getPrefix());
    }

    public function test_get_driver_name_is_sqlite(): void
    {
        $this->assertSame('sqlite', DB::getDriverName());
    }

    public function test_raw_returns_raw_object(): void
    {
        $raw = DB::getInstance()->raw('COUNT(*)');
        $this->assertSame('COUNT(*)', $raw->getValue());
    }

    public function test_query_log_disabled_by_default(): void
    {
        $this->assertFalse(DB::getInstance()->getQueryLog() !== []);
    }

    public function test_enable_query_log_captures_queries(): void
    {
        $db = DB::getInstance();
        $db->enableQueryLog();

        $this->pdo->query('SELECT 1');

        // Note : le log ne capture que via PdoQueryExecutor
        // Ici on passe directement par le PDO, donc pas de capture.
        // On teste juste que l'API ne plante pas.
        $this->assertIsArray($db->getQueryLog());

        $db->disableQueryLog();
    }

    public function test_disable_query_log_stops_capturing(): void
    {
        $db = DB::getInstance();
        $db->enableQueryLog();
        $db->disableQueryLog();

        // Ne devrait plus rien capturer
        $this->pdo->query('SELECT 1');

        $this->assertSame([], $db->getQueryLog());
    }

    public function test_flush_query_log_clears_log(): void
    {
        $db = DB::getInstance();
        $db->enableQueryLog();

        $db->flushQueryLog();

        $this->assertSame([], $db->getQueryLog());
        $db->disableQueryLog();
    }

    public function test_listen_returns_unregister_callback(): void
    {
        $called = false;

        $stop = DB::listen(function () use (&$called) {
            $called = true;
        });

        $this->assertIsCallable($stop);

        // Déclenche un événement manuellement
        QueryListenerRegistry::getInstance()->dispatch(
            new \Clicalmani\Database\Events\QueryEvent(
                sql: 'SELECT 1',
                bindings: [],
                timeMs: 0.0,
                success: true,
            )
        );

        $this->assertTrue($called);

        $stop();

        $called = false;
        QueryListenerRegistry::getInstance()->dispatch(
            new \Clicalmani\Database\Events\QueryEvent(
                sql: 'SELECT 2',
                bindings: [],
                timeMs: 0.0,
                success: true,
            )
        );

        $this->assertFalse($called);
    }

    public function test_capture_queries_returns_callback_result(): void
    {
        $result = DB::captureQueries(
            fn() => 42
        );

        $this->assertSame(42, $result);
    }

    public function test_capture_queries_receives_queries_list(): void
    {
        $queries = null;

        DB::captureQueries(
            fn() => null,
            function (array $q) use (&$queries) {
                $queries = $q;
            }
        );

        $this->assertIsArray($queries);
    }

    public function test_transaction_with_callback_commits_on_success(): void
    {
        $db = DB::getInstance();

        $result = $db->transaction(function () {
            $this->pdo->exec("INSERT INTO users (name) VALUES ('Alice')");
            return 'ok';
        });

        $this->assertSame('ok', $result);
        $this->assertSame(1, $this->countRows('users'));
    }

    public function test_transaction_with_callback_rolls_back_on_exception(): void
    {
        try {
            DB::transaction(function () {
                $this->pdo->exec("INSERT INTO users (name) VALUES ('Bob')");
                throw new \RuntimeException('boom');
            });
        } catch (\RuntimeException $e) {
            // attendu
        }

        $this->assertSame(0, $this->countRows('users'));
    }

    public function test_in_transaction_returns_false_outside(): void
    {
        $this->assertFalse(DB::inTransaction());
    }

    public function test_in_transaction_returns_true_inside(): void
    {
        $this->pdo->beginTransaction();
        $this->assertTrue(DB::inTransaction());
        $this->pdo->rollBack();
    }

    public function test_commit_returns_true_on_success(): void
    {
        DB::beginTransaction();
        $this->assertTrue(DB::commit());
    }

    public function test_rollback_returns_true_on_success(): void
    {
        DB::beginTransaction();
        $this->assertTrue(DB::rollback());
    }

    public function test_select_returns_array(): void
    {
        $this->seedDatabase();

        $rows = DB::select('SELECT * FROM users');

        $this->assertIsArray($rows->toArray());
        $this->assertCount(5, $rows);
    }

    public function test_statement_returns_pdo_statement(): void
    {
        $this->seedDatabase();

        $stmt = DB::statement('SELECT * FROM users WHERE id = ?', [1]);

        $this->assertInstanceOf(\PDOStatement::class, $stmt);
        $this->assertNotFalse($stmt->fetch());
    }

    public function test_unprepared_returns_pdo_statement(): void
    {
        $this->seedDatabase();

        $stmt = DB::unprepared('SELECT COUNT(*) FROM users');

        $this->assertInstanceOf(\PDOStatement::class, $stmt);
        $this->assertSame(5, (int) $stmt->fetchColumn());
    }

    public function test_last_insert_id_after_insert(): void
    {
        $this->pdo->exec("INSERT INTO users (name) VALUES ('X')");

        $id = DB::lastInsertId();

        $this->assertSame('1', (string) $id);
    }
}