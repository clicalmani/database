<?php 
namespace Clicalmani\Flesco\Database\Seeders;

trait DisableEventsTrigger 
{
    public function __construct()
    {
        \Clicalmani\Flesco\Models\Model::$triggerEvents = false;
    }
}
