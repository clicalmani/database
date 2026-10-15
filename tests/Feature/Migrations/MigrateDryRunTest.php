<?php

declare(strict_types=1);

namespace Database\Test\Feature\Migrations;

use Database\Test\Feature\DatabaseTestCase;
use Database\Test\Feature\Fixtures\Entities\UserEntity;
use Database\Test\Feature\Fixtures\Models\User;

final class MigrateDryRunTest extends DatabaseTestCase
{
    public function test_dry_run_does_not_create_table(): void
    {
        $entity = new UserEntity;
        $entity->setModel(new User());
        $entity->drop();

        ob_start();
        try {
           $entity->migrate(exec: false); 
        } finally {
            ob_end_clean();
        }

        // La table ne doit PAS exister
        $stmt = $this->pdo->query(
            "SELECT TABLE_NAME FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users'"
        );
        $this->assertEmpty($stmt->fetchAll());
    }

    public function test_dry_run_prints_sql_to_stdout(): void
    {
        $entity = new UserEntity;
        $entity->setModel(new User());

        ob_start();
        try {
           $entity->migrate(exec: false); 
           $output = ob_get_contents();
        } finally {
            ob_end_clean();
        }

        $this->assertNotEmpty($output);
        $this->assertStringContainsString('CREATE TABLE', $output);
        $this->assertStringContainsString('users', $output);
    }

    public function test_dry_run_output_is_valid_sql(): void
    {
        $entity = new UserEntity;
        $entity->setModel(new User());

        ob_start();
        try {
            $entity->migrate(exec: false);
            $sql = trim(ob_get_contents());
        } finally {
            ob_end_clean();
        }

        // Retirer les commentaires et points-virgules
        $sql = preg_replace('/^--.*$/m', '', $sql);
        $sql = trim($sql);
        $sql = rtrim($sql, ';');

        // Doit ressembler à du SQL
        $this->assertMatchesRegularExpression('/CREATE\s+TABLE/i', $sql);
    }

    public function test_dump_to_file(): void
    {
        $entity = new UserEntity;
        $entity->setModel(new User());

        $dumpFile = 'test_migration_' . uniqid();
        $dumpPath = database_path("manifests/$dumpFile");

        // S'assurer que le dossier existe
        @mkdir(dirname($dumpPath), 0755, true);

        try {
            $entity->migrate(exec: false, dump_file: $dumpFile);

            $dumpPath .= '.sql'; // Migrate ajoute l'extension

            $this->assertFileExists($dumpPath);

            $content = file_get_contents($dumpPath);
            $this->assertStringContainsString('CREATE TABLE', $content);
            $this->assertStringContainsString('users', $content);
        } finally {
            if (file_exists($dumpPath)) unlink($dumpPath);
        }
    }
}