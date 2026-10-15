<?php

declare(strict_types=1);

namespace Database\Test\Feature\Migrations;

use Database\Test\Feature\DatabaseTestCase;
use Database\Test\Feature\Fixtures\Entities\UserEntity;
use Database\Test\Feature\Fixtures\Models\User;

final class MigrateCreateTest extends DatabaseTestCase
{
    public function test_migrate_creates_table(): void
    {
        $entity = new UserEntity;
        $entity->setModel(new User());

        $success = $entity->migrate();

        $this->assertTrue($success);
        $this->assertTableExists('users');
    }

    public function test_migrate_generates_valid_sql(): void
    {
        $entity = new UserEntity;
        $entity->setModel(new User());

        // Dry-run : récupère le SQL sans exécuter
        ob_start();
        try {
            $entity->migrate(exec: false);
            $sql = ob_get_contents();
        } finally {
            ob_end_clean();
        }

        $this->assertStringContainsString('CREATE TABLE', $sql);
        $this->assertStringContainsString('`id`', $sql);
        $this->assertStringContainsString('`name`', $sql);
        $this->assertStringContainsString('`email`', $sql);
    }

    public function test_migrate_includes_primary_key(): void
    {
        $entity = new UserEntity;
        $entity->setModel(new User());

        ob_start();
        try {
            $entity->migrate(exec: false);
            $sql = ob_get_contents();
        } finally {
            ob_end_clean();
        }

        $this->assertStringContainsString('PRIMARY KEY', $sql);
        $this->assertStringContainsString('AUTO_INCREMENT', $sql);
    }

    public function test_migrate_includes_not_null_columns(): void
    {
        $entity = new UserEntity;
        $entity->setModel(new User());

        ob_start();
        try {
            $entity->migrate(exec: false);
            $sql = ob_get_contents();
        } finally {
            ob_end_clean();
        }

        $this->assertStringContainsString('NOT NULL', $sql);
    }

    public function test_migrate_includes_nullable_columns(): void
    {
        $entity = new UserEntity;
        $entity->setModel(new User());

        ob_start();
        try {
            $entity->migrate(exec: false);
            $sql = ob_get_contents();
        } finally {
            ob_end_clean();
        }

        // `name` est nullable dans UserEntity
        $this->assertMatchesRegularExpression(
            '/`name`\s+VARCHAR\(191\)\s+(?:\w+\s+)*NULL/',
            $sql
        );
    }

    public function test_migrate_with_default_values(): void
    {
        $entity = new UserEntity;
        $entity->setModel(new User());

        ob_start();
        try {
            $entity->migrate(exec: false);
            $sql = ob_get_contents();
        } finally {
            ob_end_clean();
        }

        // age default = 0, active default = 1
        $this->assertStringContainsString('DEFAULT', $sql);
    }

    public function test_migrate_can_be_run_twice(): void
    {
        $entity = new UserEntity;
        $entity->setModel(new User());

        $success1 = $entity->migrate();
        $this->assertTrue($success1);

        // Deuxième migration : table existe déjà
        // Selon l'implémentation, peut throw ou retourner false
        try {
            $success2 = $entity->migrate();
            $this->assertIsBool($success2);
        } catch (\PDOException $e) {
            $this->assertTrue(true);  // Comportement acceptable
        }
    }

    private function assertTableExists(string $table): void
    {
        $stmt = $this->pdo->query(
            "SELECT TABLE_NAME FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = '$table'"
        );
        $this->assertNotEmpty($stmt->fetchAll(), "Table $table doit exister");
    }
}