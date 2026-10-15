<?php
namespace Clicalmani\Database\Concerns;

enum IsolationScope: string
{
    case SESSION = 'SESSION';
    case GLOBAL  = 'GLOBAL';
    case NEXT_TRANSACTION = ''; // scope vide
}