<?php
namespace Clicalmani\Database\Concerns;

final class OrderBy
{
    public function __construct(
        private readonly string|array $column,
        private readonly string|array $order
    ) {}

    public static function from(string|array $column, string|array $order): self
    {
        $column = is_array($column) ? $column : [$column];
        $order  = is_array($order) ? $order : [$order];
        $orders = ['asc' => 'ASC', 'desc' => 'DESC'];

        $column = array_map(fn(string $col) => sanitize_attribute(trim($col)), $column);

        // The map is the whitelist: anything else becomes ''
        $order = array_map(
            fn(string $o) => $orders[strtolower(trim($o))] ?? '',
            $order
        );

        return new self($column, $order);
    }

    public function __toString(): string
    {
        $parts = [];

        foreach (array_values($this->column) as $index => $col) {
            $parts[] = rtrim($col . ' ' . ($this->order[$index] ?? 'ASC'));
        }

        return implode(', ', $parts);
    }
}