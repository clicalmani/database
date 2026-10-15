<?php
namespace Clicalmani\Database\Concerns;

enum IsolationLevel: string
{
    case READ_UNCOMMITTED = 'READ UNCOMMITTED';
    case READ_COMMITTED   = 'READ COMMITTED';
    case REPEATABLE_READ  = 'REPEATABLE READ';
    case SERIALIZABLE     = 'SERIALIZABLE';

    /**
     * Cette isolation autorise-t-elle les dirty reads ?
     */
    public function allowsDirtyReads(): bool
    {
        return $this === self::READ_UNCOMMITTED;
    }

    public function allowsNonRepeatableReads(): bool
    {
        return match ($this) {
            self::READ_UNCOMMITTED, self::READ_COMMITTED => true,
            default => false,
        };
    }

    public function allowsPhantomReads(): bool
    {
        return match ($this) {
            self::READ_UNCOMMITTED, self::READ_COMMITTED, self::REPEATABLE_READ => true,
            default => false,
        };
    }
}