<?php

declare(strict_types=1);

namespace Database\Test\Feature;

use Clicalmani\Core\Support\Facades\DB;
use Clicalmani\Database\Listeners\QueryListenerRegistry;
use PHPUnit\Framework\TestCase as BaseTestCase;
use Database\Test\Feature\Fixtures\Models\Post;
use Database\Test\Feature\Fixtures\Models\User;
use Database\Test\Feature\Fixtures\Schema;
use Database\Test\Feature\Fixtures\SchemaAdvanced;

abstract class DatabaseTestCase extends BaseTestCase
{
    protected \PDO $pdo;

    protected function setUp(): void
    {
        parent::setUp();

        $this->pdo = DB::getPdo();

        Schema::dropAll($this->pdo);
        Schema::createAll($this->pdo);
        SchemaAdvanced::createAll($this->pdo);

        QueryListenerRegistry::getInstance()->clear();
    }

    protected function tearDown(): void
    {
        Schema::dropAll($this->pdo);
        SchemaAdvanced::dropAll($this->pdo);
        QueryListenerRegistry::getInstance()->clear();
        parent::tearDown();
    }

    protected function seedDatabase(): void
    {
        Schema::seed($this->pdo);
    }

    protected function seedAdvancedDatabase(): void
    {
        // Suppliers & Accounts (pour HasOneThrough)
        $this->pdo->exec("INSERT INTO suppliers (name) VALUES ('Supplier 1'), ('Supplier 2')");
        $this->pdo->exec("INSERT INTO accounts (supplier_id, user_id, account_number) VALUES
            (1, 1, 'ACC-001'),
            (2, 2, 'ACC-002')");

        // Mechanics & Cars (pour HasOneThrough inverse)
        $this->pdo->exec("INSERT INTO mechanics (name) VALUES ('John'), ('Jane')");
        $this->pdo->exec("INSERT INTO cars (mechanic_id, brand) VALUES
            (1, 'Toyota'),
            (2, 'Honda')");

        // ─── Profiles ────────────────────────────────────────────
        $this->pdo->exec("INSERT INTO profiles (user_id, bio, avatar) VALUES
            (1, 'Bio 1', 'avatar1.jpg'),
            (2, 'Bio 2', 'avatar2.jpg'),
            (3, 'Bio 3', 'avatar3.jpg'),
            (4, 'Bio 4', 'avatar4.jpg')");
        // User 5 n'a pas de profile

        // ─── Images polymorphiques ───────────────────────────────
        $stmt = $this->pdo->prepare(
            "INSERT INTO images (imageable_id, imageable_type, url) VALUES (?, ?, ?)"
        );

        $rows = [
            [1, User::class, 'u1-1.jpg'],
            [1, User::class, 'u1-2.jpg'],
            [2, User::class, 'u2-1.jpg'],
        ];

        foreach ($rows as $row) {
            $stmt->execute($row);
        }

        // ─── Comments polymorphiques ─────────────────────────────
        $stmt = $this->pdo->prepare(
            "INSERT INTO comments (commentable_id, commentable_type, body, author_id) VALUES (?, ?, ?, ?)"
        );

        $rows = [
            [1, User::class, 'Comment on User 1', 2],
            [1, User::class, 'Another on User 1', 3],
            [2, User::class, 'Comment on User 2', 1],
            [1, Post::class, 'Comment on Post 1', 1],
            [1, Post::class, 'Another on Post 1', 2],
        ];

        foreach ($rows as $row) {
            $stmt->execute($row);
        }

        // ─── Tags ────────────────────────────────────────────────
        $this->pdo->exec("INSERT IGNORE INTO tags (name) VALUES ('php'), ('orm'), ('database')");

        $stmt = $this->pdo->prepare(
            "INSERT IGNORE INTO taggables (tag_id, taggable_id, taggable_type) VALUES (?, ?, ?)"
        );

        $rows = [
            [1, 1, User::class],
            [2, 1, User::class],
            [1, 1, Post::class],
            [3, 1, Post::class],
        ];

        foreach ($rows as $row) {
            $stmt->execute($row);
        }
    }

    protected function countRows(string $table): int
    {
        return (int) $this->pdo->query("SELECT COUNT(*) FROM `$table`")->fetchColumn();
    }

    protected function fetchAll(string $table): array
    {
        return $this->pdo->query("SELECT * FROM `$table`")->fetchAll(\PDO::FETCH_ASSOC);
    }
}