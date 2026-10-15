<?php

declare(strict_types=1);

namespace Database\Test\Feature\Migrations;

use Database\Test\Feature\DatabaseTestCase;
use Database\Test\Feature\Fixtures\Entities\UserEntity;
use Database\Test\Feature\Fixtures\Models\User;

final class DropTest extends DatabaseTestCase
{
    public function test_drop_removes_table(): void
    {
        $this->seedDatabase();

        // La table existe
        $this->assertTableExists('users');

        $entity = new UserEntity;
        $entity->setModel(new User());
        $success = $entity->drop();

        $this->assertTrue($success);
        $this->assertTableNotExists('users');
    }

    public function test_drop_returns_true_when_table_exists(): void
    {
        $this->seedDatabase();

        $entity = new UserEntity;
        $entity->setModel(new User());
        $result = $entity->drop();

        $this->assertTrue($result);
    }

    public function test_drop_with_foreign_key_check_disabled(): void
    {
        $this->seedDatabase();

        // Posts référence users → on désactive la contrainte
        $entity = new UserEntity;
        $entity->setModel(new User());
        $success = $entity->drop(foreign_key_check: true);

        $this->assertTrue($success);
    }

    public function test_drop_if_exists_does_not_throw(): void
    {
        $entity = new UserEntity;
        $entity->setModel(new User());
        $result = $entity->drop();

        // La table n'existe pas
        $this->assertTableNotExists('users');

        // Ne doit PAS throw grâce au IF EXISTS
        $this->assertTrue($result);
    }

    // ─── Helpers ──────────────────────────────────────────

    private function assertTableExists(string $table): void
    {
        $stmt = $this->pdo->query(
            "SELECT TABLE_NAME FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = '$table'"
        );
        $this->assertNotEmpty($stmt->fetchAll(), "Table $table doit exister");
    }

    private function assertTableNotExists(string $table): void
    {
        $stmt = $this->pdo->query(
            "SELECT TABLE_NAME FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = '$table'"
        );
        $this->assertEmpty($stmt->fetchAll(), "Table $table ne doit PAS exister");
    }
}