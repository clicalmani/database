<?php

declare(strict_types=1);

namespace Database\Test\Feature\Migrations;

use Database\Test\Feature\DatabaseTestCase;

final class MigrateAlterTest extends DatabaseTestCase
{
    /**
     * Test qu'une entité avec #[AlterOption] génère bien une migration ALTER.
     * Nécessite une fixture avec AlterOption.
     */
    public function test_alter_option_generates_alter_sql(): void
    {
        $this->markTestIncomplete(
            'Nécessite une fixture avec #[AlterOption]'
        );
    }

    public function test_alter_option_add_column(): void
    {
        $this->markTestIncomplete(
            'Nécessite une fixture avec AlterOption->addColumn()'
        );
    }

    public function test_alter_option_drop_column(): void
    {
        $this->markTestIncomplete(
            'Nécessite une fixture avec AlterOption->dropColumn()'
        );
    }

    public function test_alter_option_custom_handler(): void
    {
        $this->markTestIncomplete(
            'Nécessite une fixture avec AlterOption(handler: ...)'
        );
    }
}