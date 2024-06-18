<?php 
namespace Clicalmani\Database\Factory\Models;

trait HasFactory
{
    public static function seed()
    {
        $className = get_called_class();
        $model = substr($className, strrpos($className, "\\") + 1);
        
        $factory = "\\Database\\Factories\\" . $model . 'Factory';
        
        return $factory::new();
    }
}
