<?php
namespace Clicalmani\Database\Concerns;

trait IfExists
{
    protected function ifExistsClause(): string
    {
        return !empty($this->params['exists']) ? ' IF EXISTS' : '';
    }
}