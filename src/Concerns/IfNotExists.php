<?php
namespace Clicalmani\Database\Concerns;

trait IfNotExists
{
    protected function ifNotExistsClause(): string
    {
        return !empty($this->params['not_exists']) ? ' IF NOT EXISTS' : '';
    }
}