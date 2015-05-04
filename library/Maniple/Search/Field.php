<?php

class Maniple_Search_Field implements Maniple_Search_FieldInterface
{
    const FIELD_DEFAULT     = 0;
    const FIELD_TOKENIZABLE = 1;
    const FIELD_UNIQUE      = 2;

    /**
     * @var string
     */
    protected $_name;

    /**
     * @var mixed
     */
    protected $_value;

    /**
     * @var int
     */
    protected $_flags = 0;

    /**
     * Constructor.
     *
     * @param  string $name
     * @param  mixed $value
     * @param  int $flags
     */
    public function __construct($name, $value, $flags = self::FIELD_DEFAULT) // {{{
    {
        $this->_name = (string) $name;
        $this->_value = $value;
        $this->_flags = (int) $flags;
    } // }}}

    /**
     * Retrieves field name.
     *
     * @return string
     */
    public function getName() // {{{
    {
        return $this->_name;
    } // }}}

    /**
     * Retrieves field value.
     *
     * @return mixed
     */
    public function getValue() // {{{
    {
        return $this->_value;
    } // }}}

    /**
     * Is this field tokenizable?
     *
     * @return bool
     */
    public function isTokenizable() // {{{
    {
        return (bool) ($this->_flags & self::FIELD_TOKENIZABLE);
    } // }}}

    /**
     * Is this field unique?
     *
     * @return bool
     */
    public function isUnique() // {{{
    {
        return (bool) ($this->_flags & self::FIELD_UNIQUE);
    } // }}}

    /**
     * Factory for tokenizable string fields.
     *
     * @param  string $name
     * @param  string $value
     * @return Maniple_Search_Field
     */
    public static function Text($name, $value) // {{{
    {
        return new self($name, (string) $value, self::FIELD_TOKENIZABLE);
    } // }}}

    /**
     * Factory for non-tokenizable fields (meta-fields).
     *
     * @param  string $name
     * @param  mixed $value
     * @return Maniple_Search_Field
     */
    public static function Meta($name, $value) // {{{
    {
        return new self($name, $value, self::FIELD_DEFAULT);
    } // }}}

    /**
     * Factory for non-tokenizable, unique field (ID).
     *
     * @param  string $name
     * @param  mixed $value
     * @return Maniple_Search_Field
     */
    public static function Id($name, $value) // {{{
    {
        return new self($name, $value, self::FIELD_UNIQUE);
    } // }}}
}
