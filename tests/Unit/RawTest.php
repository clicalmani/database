<?php

declare(strict_types=1);

namespace Database\Test\Unit;

use Clicalmani\Database\Concerns\Raw;
use PHPUnit\Framework\TestCase;

final class RawTest extends TestCase
{
    public function test_stores_expression(): void
    {
        $raw = new Raw('COUNT(*)');
        $this->assertSame('COUNT(*)', $raw->getValue());
    }

    public function test_casts_to_string(): void
    {
        $raw = new Raw('NOW()');
        $this->assertSame('NOW()', (string) $raw);
    }

    public function test_accepts_complex_expression(): void
    {
        $expr = "CONCAT(first_name, ' ', last_name)";
        $raw  = new Raw($expr);
        $this->assertSame($expr, $raw->getValue());
    }
}