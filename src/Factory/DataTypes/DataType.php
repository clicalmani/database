<?php
namespace Clicalmani\Database\Factory\DataTypes;

/**
 * Class DataType
 * 
 * @package Clicalmani\Database
 * @author @clicalmani
 */
class DataType implements \JsonSerializable
{
    use Numeric,
        Character,
        Spatial,
        JSON,
        Date;

    /**
     * Integer length
     * 
     * @var int
     */
    const INTEGER_LENGTH = 10;

    /**
     * TinyInt length
     * 
     * @var int
     */
    const TINYINT_LENGTH = 3;

    /**
     * Small integer
     * 
     * @var int
     */
    const SMALLINT_LENGTH = 5;

    /**
     * Decimal scale
     * 
     * @var int
     */
    const DECIMAL_SCALE = 12;

    /**
     * Decimal precision
     * 
     * @var int
     */
    const DECIMAL_PRECISION = 4;

    /**
     * String length
     * 
     * @var int
     */
    const STRING_LENGTH = 255;

    /**
     * Character length
     * 
     * @var int
     */
    const CHARACTER_LENGTH = 1;

    /**
     * Value
     * 
     * @var mixed
     */
    protected $value;

    /**
     * Store assigned data type.
     * 
     * @var string
     */
    protected string $type = '';

    /**
     * Data formatter
     * 
     * @var ?string
     */
    protected ?string $formatter = null;

    /**
     * Data
     * 
     * @var string
     */
    protected string $data = '';

    public function __construct(string $data = '') {
        $this->data = $data;
    }

    /**
     * Set null data type
     * 
     * @param ?bool $null Default to true
     * @return static
     */
    public function nullable(?bool $null = true) : static
    {
        $this->data .= $null ? ' NULL': ' NOT NULL';
        return $this;
    }

    /**
     * Set default value
     * 
     * @param ?string $value Default value
     * @return static
     */
    public function default(?string $value = NULL) : static
    {
        $this->data .= ' DEFAULT ' . ((NULL !== $value) ? "'$value'": 'NULL');
        return $this;
    }

    /**
     * Shorthand for default when default value is null.
     * 
     * @return static
     */
    public function defaultNull() : static
    {
        $this->data .= ' DEFAULT NULL';
        return $this;
    }

    /**
     * Unique index
     * 
     * @return static
     */
    public function unique() : static
    {
        $this->data .= ' UNIQUE';
        return $this;
    }

    /**
     * Primary key
     * 
     * @return static
     */
    public function primary() : static
    {
        $this->data .= ' PRIMARY KEY';
        return $this;
    }

    /**
     * Comment a data
     * 
     * @param ?string $comment Data comment
     * @return static
     */
    public function comment(?string $comment = '') : static
    {
        $this->data .= ' COMMENT "' . $comment . '"';
        return $this;
    }

    private function join(array $arr) : string
    {
        $value = '';

        foreach ($arr as $index => $val) {
            if ($index < count($arr) - 1) $value .= "'$val', ";
            else $value .= "'$val'";
        }

        return $value;
    }

    /**
     * Returns data
     * 
     * @return string
     */
    public function getData() : string
    {
        return $this->data;
    }

    /**
     * Type getter
     * 
     * @return string
     */
    public function getType() : string
    {
        return $this->type;
    }

    /**
     * Value getter
     * 
     * @return mixed
     */
    public function getValue() : mixed
    {
        return $this->value;
    }

    /**
     * Cast value to data type
     * 
     * @param mixed $value Value to cast
     * @return mixed
     */
    public function cast(mixed $value) : mixed
    {
        return $value;
    }

    /**
     * Cast the value to be sent to the database.
     * 
     * @param mixed $value
     * @return mixed
     */
    public function toDatabase($value) : mixed
    {
        return $value;
    }

    /**
     * Returns the data formatter
     * 
     * @return ?string
     */
    public function getFormatter() : ?string
    {
        return $this->formatter;
    }

    protected function sharedOptions(array $options): void
    {
        // ── Default Value ────────────────────────────────────────────────
        $default_value = $options['default'] ?? null;
        $default_value = match (gettype($default_value)) {
            'boolean' => (int) $default_value,
            'integer' => "$default_value",
            'string'  => $default_value,
            default   => null
        };
        if (isset($default_value)) $this->default($default_value);

        // ── Comment ──────────────────────────────────────────────────────
        if ($comment = $options['comment'] ?? null) $this->comment($comment);

        // ── Fortmatter ───────────────────────────────────────────────────
        // Format data
        $this->formatter = $options['formatter'] ?? null;
    }

    /**
     * PHP magic __call
     * 
     * @param string $method Method to call
     * @param mixed $params Arguments
     * @return void
     */
    public function __call(string $method, mixed $params) : void
    {
        if (method_exists($this, $method)) $this->{$method}(...$params);
        else throw new \Clicalmani\Database\Exceptions\DataTypeException("The method $method is not associated to any data type.");
    }

    public function __set($name, $value)
    {
        $this->type = gettype($value);
        $this->{$name} = $value; // $name should always be egal "value"
    }

    public function __toString()
    {
        return $this->value;
    }

    public function jsonSerialize(): mixed
    {
        return $this->value;
    }
}
