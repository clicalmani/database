<?php
namespace Clicalmani\Database\Factory\Models;

interface Joinable
{
    /**
     * Custom join
     * 
     * @param string|callable|\Clicalmani\Database\Factory\Models\Elegant $model Specified model
     * @param ?callable $callback A callback function
     * @return static
     */
    public function join(string|callable|Elegant $model, ?callable $callback = null): static;

    /**
     * Left join models
     * 
     * @param string|\Clicalmani\Database\Factory\Models\Elegant $model Specified model
     * @param ?string $foreign_key [Optional] Foreign key
     * @param ?string $original_key [Optional] Original key
     * @return static
     */
    public function leftJoin(Elegant|string $model, string|null $foreign_key = null, string|null $original_key = null) : static;

    /**
     * Right join models
     * 
     * @param string|\Clicalmani\Database\Factory\Models\Elegant $model Specified model
     * @param ?string $foreign_key [Optional] Foreign key
     * @param ?string $original_key [Optional] Original key
     * @return static
     */
    public function rightJoin(Elegant|string $model, string|null $foreign_key = null, string|null $original_key = null) : static;

    /**
     * Inner join models
     * 
     * @param string|\Clicalmani\Database\Factory\Models\Elegant $model Specified model
     * @param ?string $foreign_key [Optional] Foreign key
     * @param ?string $original_key [Optional] Original key
     * @return static
     */
    public function innerJoin(Elegant|string $model, string|null $foreign_key = null, string|null $original_key = null) : static;

    /**
     * Cross join models
     * 
     * @param string|\Clicalmani\Database\Factory\Models\Elegant $model Specified model
     * @param ?string $foreign_key [Optional] Foreign key
     * @param ?string $original_key [Optional] Original key
     * @return static
     */
    public function crossJoin(Elegant|string $model) : static;
}
