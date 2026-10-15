<?php

declare(strict_types=1);

namespace Database\Test\Feature\Migrations;

use Database\Test\Feature\DatabaseTestCase;
use Database\Test\Feature\Fixtures\Entities\PostEntity;
use Database\Test\Feature\Fixtures\Models\Post;

final class MigrateForeignKeyTest extends DatabaseTestCase
{
    public function test_migrate_includes_foreign_key(): void
    {
        $entity = new PostEntity;
        $entity->setModel(new Post());

        ob_start();
        try {
            $entity->migrate(exec: false);
            $sql = ob_get_contents();
        } finally {
            ob_end_clean();
        }

        $this->assertStringContainsString('CREATE TABLE', $sql);
        $this->assertStringContainsString('`posts`', $sql);
    }

    public function test_migrate_with_constraint(): void
    {
        // Nécessite une entity avec #[Index(constraint: ..., references: ...)]
        $this->markTestIncomplete(
            'Nécessite une fixture avec constraint foreign key'
        );
    }

    public function test_migrate_with_on_delete_cascade(): void
    {
        $this->markTestIncomplete(
            'Nécessite une fixture avec onDelete: Index::ON_DELETE_CASCADE'
        );
    }

    public function test_migrate_with_on_delete_set_null(): void
    {
        $this->markTestIncomplete(
            'Nécessite une fixture avec onDelete: Index::ON_DELETE_SETNULL'
        );
    }
}