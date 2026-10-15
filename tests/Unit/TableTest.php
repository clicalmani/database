<?php

declare(strict_types=1);

namespace Database\Test\Unit;

use Clicalmani\Database\Factory\Models\Table;
use PHPUnit\Framework\TestCase;

final class TableTest extends TestCase
{
    public function test_parses_simple_name(): void
    {
        $table = Table::from('users');
        $this->assertSame('users', $table->name());
        $this->assertFalse($table->isAliased());
    }

    public function test_parses_name_with_alias(): void
    {
        $table = Table::from('users u');
        $this->assertSame('users', $table->name());
        $this->assertTrue($table->isAliased());
        $this->assertSame('u', $table->alias());
    }

    public function test_parses_name_with_as_alias(): void
    {
        $table = Table::from('users AS u');
        $this->assertSame('users', $table->name());
        $this->assertSame('u', $table->alias());
    }

    public function test_case_insensitive_as(): void
    {
        $table = Table::from('users as u');
        $this->assertSame('u', $table->alias());
    }

    public function test_alias_falls_back_to_name(): void
    {
        $table = Table::from('users');
        $this->assertSame('users', $table->alias());
    }

    public function test_with_alias_returns_name_and_alias(): void
    {
        $table = Table::from('users u');
        $this->assertSame('users u', $table->withAlias());
    }

    public function test_with_alias_returns_name_when_not_aliased(): void
    {
        $table = Table::from('users');
        $this->assertSame('users', $table->withAlias());
    }

    public function test_to_string(): void
    {
        $table = Table::from('users u');
        $this->assertSame('users u', (string) $table);
    }

    public function test_deprecated_alised(): void
    {
        $table = Table::from('users u');
        $this->assertTrue($table->alised());
    }
}