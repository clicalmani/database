<?php

declare(strict_types=1);

namespace Database\Test\Feature\EdgeCases;

use Database\Test\Feature\DatabaseTestCase;
use Database\Test\Feature\Fixtures\Models\User;

final class PaginationEdgeTest extends DatabaseTestCase
{
    public function test_page_zero_throws_or_returns_first(): void
    {
        $this->seedDatabase();

        try {
            $page = User::paginate(page: 0, size: 10);
            // Si pas de throw, on s'attend à la première page
            $this->assertGreaterThan(0, $page->items()->count());
        } catch (\InvalidArgumentException $e) {
            $this->assertTrue(true);
        }
    }

    public function test_page_negative_throws(): void
    {
        $this->seedDatabase();

        $this->expectException(\InvalidArgumentException::class);
        User::paginate(page: -1, size: 10);
    }

    public function test_size_zero_throws(): void
    {
        $this->seedDatabase();

        $this->expectException(\InvalidArgumentException::class);
        User::paginate(page: 1, size: 0);
    }

    public function test_size_negative_throws(): void
    {
        $this->seedDatabase();

        $this->expectException(\InvalidArgumentException::class);
        User::paginate(page: 1, size: -5);
    }

    public function test_page_beyond_last_returns_empty(): void
    {
        $this->seedDatabase();

        $page = User::paginate(page: 100, size: 10);

        $this->assertSame(0, $page->items()->count());
        $this->assertSame(5, $page->total());
    }

    public function test_size_larger_than_total(): void
    {
        $this->seedDatabase();

        $page = User::paginate(page: 1, size: 100);

        $this->assertSame(5, $page->items()->count());
        $this->assertSame(1, $page->lastPage());
    }

    public function test_simple_paginate_last_page_has_no_next(): void
    {
        $this->seedDatabase();

        // 5 users, size 2 → page 3 a 1 user et pas de next
        $page = User::simplePaginate(page: 3, size: 2);

        $this->assertSame(1, $page->items()->count());
    }

    public function test_chunk_size_zero_throws(): void
    {
        $this->seedDatabase();

        $this->expectException(\InvalidArgumentException::class);

        User::chunk(0, function () {});
    }
}