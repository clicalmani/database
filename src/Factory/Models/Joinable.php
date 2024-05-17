<?php
namespace Clicalmani\Database\Factory\Models;

interface Joinable
{
    /**
     * Join models
     * 
     * @param string|\Clicalmani\Database\Factory\Models\Model $model Specified model
     * @param ?string $foreign_key Foreign key
     * @param ?string $original_key Original key
     * @param ?string $type Join type default LEFT
     * @return static
     */
    public function join(Model|string $model, string|null $foreign_key = null, string|null $original_key = null, string $type = 'LEFT') : static;

    /**
     * Left join models
     * 
     * @param string|\Clicalmani\Database\Factory\Models\Model $model Specified model
     * @param ?string $foreign_key [Optional] Foreign key
     * @param ?string $original_key [Optional] Original key
     * @return static
     */
    public function leftJoin(Model|string $model, string|null $foreign_key = null, string|null $original_key = null) : static;

    /**
     * Right join models
     * 
     * @param string|\Clicalmani\Database\Factory\Models\Model $model Specified model
     * @param ?string $foreign_key [Optional] Foreign key
     * @param ?string $original_key [Optional] Original key
     * @return static
     */
    public function rightJoin(Model|string $model, string|null $foreign_key = null, string|null $original_key = null) : static;

    /**
     * Inner join models
     * 
     * @param string|\Clicalmani\Database\Factory\Models\Model $model Specified model
     * @param ?string $foreign_key [Optional] Foreign key
     * @param ?string $original_key [Optional] Original key
     * @return static
     */
    public function innerJoin(Model|string $model, string|null $foreign_key = null, string|null $original_key = null) : static;

    /**
     * Cross join models
     * 
     * @param string|\Clicalmani\Database\Factory\Models\Model $model Specified model
     * @param ?string $foreign_key [Optional] Foreign key
     * @param ?string $original_key [Optional] Original key
     * @return static
     */
    public function crossJoin(Model|string $model) : static;
}
