<?php
namespace Clicalmani\Database\Concerns;

trait Temporary
{
    protected function temporaryClause(): string
    {
        return !empty($this->params['temporary']) ? 'TEMPORARY ' : '';
    }
}