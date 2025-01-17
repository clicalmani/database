<?php
namespace Clicalmani\Database\Factory\Models;

Trait SingleKey
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
        
        throw new \InvalidArgumentException("String expected for $value; got " . gettype($value));
    }

    /**
     * Guess key value
     * 
     * @param array $row
     */
    public function guessKeyValue(array $row)
    {
        $key = $this->cleanKey( $this->getKey() );
        
        if (is_string($key)) return @ $row[$key];

        throw new \InvalidArgumentException("String expected for $key; got array " . json_encode($this->id));
    }

    /**
     * Prepare a SQL condition for key value.
     * 
     * @param ?bool $allow_alias
     * @return string
     */
    public function getKeySQLCondition(?bool $allow_alias = false) : string
    {
        $keys = $this->getKey($allow_alias);
        
        if ( is_string($keys) ) {

            /**
             * |--------------------------------------------
             * |          ***** Warning *****
             * |--------------------------------------------
             * 
             * A string key type could not accept an array value.
             * This would result to a simple warning. But to avoid a possible mislead we just throw an exception
             */
            if ( ! is_string($this->id) ) throw new \InvalidArgumentException("String expected for $keys; got array " . json_encode($this->id));
            
            return "$keys = '$this->id'";
        
        }

        throw new \InvalidArgumentException("String expected for $keys; got array " . json_encode($this->id));
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
