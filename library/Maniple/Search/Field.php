<?php

class Maniple_Search_Field implements Maniple_Search_FieldInterface
{
    /**
     * @var string
     */
    protected $_name;

    /**
     * @var mixed
     */
    protected $_value;

    /**
     * @var bool
     */
    protected $_isTokenizable = true;

    /**
     * Constructor.
     *
     * @param  string $name
     * @param  mixed $value
     * @param  bool $isTokenizable
     * @return void
     */
    public function __construct($name, $value, $isTokenizable) // {{{
    {
        $this->_name = (string) $name;
        $this->_value = $value;
        $this->_isTokenizable = $isTokenizable;
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
        return $this->_isTokenizable;
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
        return new self($name, (string) $value, true);
    } // }}}

    /**
     * Factory for non-tokenizable string fields (meta-fields).
     *
     * @param  string $name
     * @param  mixed $value
     * @return Maniple_Search_Field
     */
    public static function Meta($name, $value) // {{{
    {
        return new self($name, $value, false);
    } // }}}
}
