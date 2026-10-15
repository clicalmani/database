<?php

declare(strict_types=1);

namespace Database\Test;

use Clicalmani\Database\Listeners\QueryListenerRegistry;
use PHPUnit\Framework\TestCase as BaseTestCase;

/**
 * Base class for unit/feature tests that need a real SQLite database.
 *
 * Coexists with Clicalmani\Core\Test\TestCase (which is HTTP-oriented).
 */
abstract class UnitTestCase extends BaseTestCase
{
    protected \PDO $pdo;

    /** @var string[] Tables created in the current test. */
    private array $createdTables = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->pdo = \Clicalmani\Core\Support\Facades\DB::getInstance()->getPdo();
        $this->pdo->exec('PRAGMA foreign_keys = ON');

        QueryListenerRegistry::getInstance()->clear();
    }

    protected function tearDown(): void
    {
        $this->pdo->exec('PRAGMA foreign_keys = OFF');

        foreach (array_reverse($this->createdTables) as $table) {
            $this->pdo->exec("DROP TABLE IF EXISTS \"$table\"");
        }

        $this->pdo->exec('PRAGMA foreign_keys = ON');
        $this->createdTables = [];

        QueryListenerRegistry::getInstance()->clear();
        parent::tearDown();
    }

    protected function createTable(string $sql, ?string $tableName = null): void
    {
        $this->pdo->exec($sql);

        if ($tableName === null
            && \preg_match('/CREATE\s+TABLE\s+(?:IF\s+NOT\s+EXISTS\s+)?[`"]?(\w+)[`"]?/i', $sql, $m)) {
            $tableName = $m[1];
        }

        if ($tableName) $this->createdTables[] = $tableName;
    }

    protected function seed(string $table, array $rows): void
    {
        if (empty($rows)) return;

        $columns      = array_keys($rows[0]);
        $quoted       = array_map(fn($c) => "\"$c\"", $columns);
        $placeholders = implode(', ', array_fill(0, count($columns), '?'));

        $sql  = sprintf(
            'INSERT INTO "%s" (%s) VALUES (%s)',
            $table,
            implode(', ', $quoted),
            $placeholders
        );
        $stmt = $this->pdo->prepare($sql);

        foreach ($rows as $row) $stmt->execute(array_values($row));
    }

    protected function countRows(string $table): int
    {
        return (int) $this->pdo->query("SELECT COUNT(*) FROM \"$table\"")->fetchColumn();
    }

    protected function fetchAll(string $table): array
    {
        return $this->pdo->query("SELECT * FROM \"$table\"")->fetchAll(\PDO::FETCH_ASSOC);
    }
}