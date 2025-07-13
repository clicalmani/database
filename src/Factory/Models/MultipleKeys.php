<?php
namespace Clicalmani\Database\Factory\Models;

use Clicalmani\Foundation\Support\Facades\DB;

Trait MultipleKeys
{
    /**
     * Removes table alias from the key
     * 
     * @param mixed $value Keys to be clean
     * @return mixed cleaned key(s)
     */
    public function cleanKey(mixed $value) : mixed
    {
        if (!$value) return false;

        /**
         * Single key table
         */
        if ( is_string($value) ) return $this->substractKey(trim($value));
        
        return collection($value)
                    ->map(fn(string $v) => $this->substractKey(trim($v)))
                    ->toArray();
    }

    /**
     * Guess key value
     * 
     * @param array $row
     * @return string|array|null
     */
    public function guessKeyValue(array $row) : string|array|null
    {
        $key = $this->cleanKey( $this->getKey() );
        
        if ( is_array($key) ) {

            $ids = [];

            foreach ($key as $k) {
                if (array_key_exists($k, $row) && isset($row[$k])) $ids[] = $row[$k];
            }
            
            return $ids;
        }
        
        return @ $row[$key];
    }

    /**
     * Prepare a SQL condition for key value.
     * 
     * @param ?bool $allow_alias
     * @return string
     */
    public function getKeySQLCondition(?bool $allow_alias = false) : string
    {
        $keys     = $this->getKey($allow_alias);
        $criteria = null;
        
        if ( is_string($keys) ) {

            /**
             * |--------------------------------------------
             * |          ***** Warning *****
             * |--------------------------------------------
             * 
             * A string key type could not accept an array value.
             * This would result to a simple warning. But to avoid a possible mislead we just throw an exception
             */
            if ( is_array($this->id) ) throw new \InvalidArgumentException("String expected for $keys; got array " . json_encode($this->id));
            
            @[$table, $alias] = explode(' ', $this->getTable(true), 2);
            
            $table = isset($alias) ? $alias: DB::getInstance()->getPrefix() . $table;
            $keys = $this->substractKey($keys);
            $criteria = "$table.$keys" . ' = "' . $this->id . '"';
        
        } elseif ( is_array($keys) ) {

            /**
             * |--------------------------------------------
             * |          ***** Warning *****
             * |--------------------------------------------
             * 
             * An array key type could not accept a string value.
             * This would result to a simple warning. But to avoid a possible mislead we just throw an exception
             */
            if ( ! is_array($this->id) ) throw new \InvalidArgumentException("Array expected for key " . json_encode($keys) . "; got string $this->id");
            
            if ( count($keys) !== count($this->id) ) throw new \InvalidArgumentException("Keys count should match values count for " . json_encode($keys) . "; got " . json_encode($this->id));
            
            $criterias = [];
            
            for ($i=0; $i<count($keys); $i++) {
                $criterias[] = $keys[$i] . '="' . $this->id[$i] . '"';
            }

            $criteria = join(' AND ', $criterias);
        }
        
        return $criteria;
    }

    /**
     * Substract key from the value.
     * 
     * @param string $value
     * @return string
     */
    public function substractKey(string $value) : string
    {
        @[$alias, $key] = explode('.', $value);
        return $key ? $key: $value;
    }
}
