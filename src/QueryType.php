<?php
namespace Clicalmani\Database;

enum QueryType: int
{
    case SELECT              = 0;
    case INSERT              = 1;
    case DELETE              = 2;
    case UPDATE              = 3;
    case CREATE              = 4;
    case DROP_TABLE          = 5;
    case DROP_TABLE_IF_EXISTS = 6;
    case ALTER               = 7;
    case LOCK_TABLE          = 8;
    case UNLOCK_TABLE        = 9;
    case REPLACE             = 10;
    case TRUNCATE            = 11;
    case UNION               = 12;
    case SHOW_COLUMNS        = 13;

    /**
     * Cette requête renvoie-t-elle un PDOStatement exploitable (fetch) ?
     */
    public function producesStatement(): bool
    {
        return match ($this) {
            self::SELECT, self::DELETE => true,
            default => false,
        };
    }

    public function usesPreparedStatement(): bool
    {
        return match ($this) {
            self::INSERT, self::UPDATE, self::REPLACE => true,
            default => false,
        };
    }

    /**
     * Cette requête retourne-t-elle des lignes à hydrater ?
     */
    public function isFetchable(): bool
    {
        return $this === self::SELECT;
    }

    /**
     * Faut-il calculer FOUND_ROWS() (pagination) ?
     */
    public function supportsFoundRows(): bool
    {
        return $this === self::SELECT;
    }

    /**
     * Mapping vers la classe builder correspondante.
     */
    public function builderClass(): string
    {
        return match ($this) {
            self::SELECT               => \Clicalmani\Database\Select::class,
            self::INSERT               => \Clicalmani\Database\Insert::class,
            self::UPDATE               => \Clicalmani\Database\Update::class,
            self::DELETE               => \Clicalmani\Database\Delete::class,
            self::REPLACE              => \Clicalmani\Database\Replace::class,
            self::TRUNCATE             => \Clicalmani\Database\Truncate::class,
            self::CREATE               => \Clicalmani\Database\Factory\Create::class,
            self::ALTER                => \Clicalmani\Database\Factory\Alter::class,
            self::DROP_TABLE           => \Clicalmani\Database\Factory\Drop::class,
            self::DROP_TABLE_IF_EXISTS => \Clicalmani\Database\Factory\Drop::class,
            self::LOCK_TABLE           => \Clicalmani\Database\Lock::class,
            self::UNLOCK_TABLE         => \Clicalmani\Database\Unlock::class,
        };
    }
}