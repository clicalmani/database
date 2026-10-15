<?php

declare(strict_types=1);

namespace Database\Test\Unit;

use Clicalmani\Database\Concerns\Raw;
use Clicalmani\Database\Concerns\SqlBinder;
use PHPUnit\Framework\TestCase;

final class SqlBinderTest extends TestCase
{
    public function test_positional_bindings_preserved(): void
    {
        [$sql, $bindings] = SqlBinder::resolve(
            'SELECT * FROM users WHERE id = ? AND name = ?',
            [1, 'Alice']
        );
        $this->assertSame('SELECT * FROM users WHERE id = ? AND name = ?', $sql);
        $this->assertSame([1, 'Alice'], $bindings);
    }

    public function test_named_bindings_preserved(): void
    {
        [$sql, $bindings] = SqlBinder::resolve(
            'SELECT * FROM users WHERE id = :id',
            ['id' => 5]
        );
        $this->assertSame('SELECT * FROM users WHERE id = :id', $sql);
        $this->assertSame([':id' => 5], $bindings);
    }

    public function test_raw_bindings_are_inlined(): void
    {
        [$sql, $bindings] = SqlBinder::resolve(
            'SELECT * FROM users WHERE created_at > :now',
            ['now' => new Raw('NOW()')]
        );
        $this->assertSame('SELECT * FROM users WHERE created_at > NOW()', $sql);
        $this->assertArrayNotHasKey('now', $bindings);
    }

    public function test_strings_with_placeholders_ignored(): void
    {
        [$sql, $bindings] = SqlBinder::resolve(
            "SELECT * FROM users WHERE name = ? AND note = '?'",
            ['Alice']
        );
        $this->assertSame("SELECT * FROM users WHERE name = ? AND note = '?'", $sql);
        $this->assertSame(['Alice'], $bindings);
    }

    public function test_missing_positional_binding_throws(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        SqlBinder::resolve('SELECT * FROM users WHERE id = ? AND name = ?', [1]);
    }

    public function test_missing_named_binding_throws(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        SqlBinder::resolve('SELECT * FROM users WHERE id = :id', []);
    }

    public function test_interpolate_replaces_named(): void
    {
        $sql = SqlBinder::interpolate(
            'SELECT * FROM users WHERE id = :id AND name = :name',
            ['id' => 5, 'name' => 'Alice']
        );
        $this->assertSame("SELECT * FROM users WHERE id = 5 AND name = 'Alice'", $sql);
    }

    public function test_interpolate_handles_null(): void
    {
        $sql = SqlBinder::interpolate('SELECT * WHERE x = :x', ['x' => null]);
        $this->assertSame('SELECT * WHERE x = NULL', $sql);
    }

    public function test_interpolate_handles_bool(): void
    {
        $sql = SqlBinder::interpolate('SELECT * WHERE x = :x', ['x' => true]);
        $this->assertSame('SELECT * WHERE x = 1', $sql);
    }

    public function test_interpolate_escapes_quotes(): void
    {
        $sql = SqlBinder::interpolate('SELECT * WHERE n = :n', ['n' => "O'Brien"]);
        $this->assertSame("SELECT * WHERE n = 'O''Brien'", $sql);
    }
}