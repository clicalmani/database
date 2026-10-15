<?php

declare(strict_types=1);

namespace Database\Test\Unit;

use Clicalmani\Database\Concerns\OrderBy;
use PHPUnit\Framework\TestCase;

final class OrderByTest extends TestCase
{
    public function test_single_column_asc(): void
    {
        $o = OrderBy::from('name', 'ASC');
        $this->assertSame('name ASC', (string) $o);
    }

    public function test_single_column_desc(): void
    {
        $o = OrderBy::from('name', 'DESC');
        $this->assertSame('name DESC', (string) $o);
    }

    public function test_lowercase_order_is_normalized(): void
    {
        $o = OrderBy::from('name', 'asc');
        $this->assertSame('name ASC', (string) $o);
    }

    public function test_invalid_order_is_ignored(): void
    {
        $o = OrderBy::from('name', 'RANDOM');
        $this->assertSame('name', (string) $o);
    }

    public function test_multiple_columns(): void
    {
        $o = OrderBy::from(['name', 'age'], ['ASC', 'DESC']);
        $str = (string) $o;
        $this->assertStringContainsString('name ASC', $str);
        $this->assertStringContainsString('age DESC', $str);
    }

    public function test_from_returns_instance(): void
    {
        $this->assertInstanceOf(OrderBy::class, OrderBy::from('name', 'ASC'));
    }
}