<?php 
namespace Clicalmani\Database\Traits;

trait DisableSQLEventsTrigger 
{
    public function __construct()
    {
        \Clicalmani\Database\Factory\Models\Model::$triggerEvents = false;
    }
}
