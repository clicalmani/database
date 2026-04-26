<?php
namespace Clicalmani\Database\Factory\Models\Relations;

abstract class Relationship
{
    abstract public function get(): mixed;
    abstract protected function getParentClass(): string;

    protected function getCallerClassFromNew(): ?string
    {
        $trace = debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT, 4);

        if (isset($trace[3], $trace[3]['class'])) {
            return $trace[3]['class'];
        }

        return null;
    }
}