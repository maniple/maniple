<?php

class Maniple_Search_Document
    implements Maniple_Search_DocumentInterface
{
    /**
     * @var Maniple_Search_FieldInterface[]
     */
    protected $_fields = array();

    /**
     * Adds field to document.
     *
     * @param  Maniple_Search_FieldInterface $field
     * @return Maniple_Search_Document
     */
    public function addField(Maniple_Search_FieldInterface $field) // {{{
    {
        $this->_fields[(string) $field->getName()] = $field;
        return $this;
    } // }}}

    /**
     * Retrieves field corresponding to a given name.
     *
     * @param  string $name
     * @return Maniple_Search_FieldInterface|null
     */
    public function getField($name) // {{{
    {
        if (isset($this->_fields[$name])) {
            return $this->_fields[$name];
        }
        return null;
    } // }}}

    /**
     * Retrieves list of all fields in this document.
     *
     * @return Maniple_Search_FieldInterface[]
     */
    public function getFields() // {{{
    {
        return $this->_fields;
    } // }}}

    /**
     * Retrieves list of names of all fields present in this document.
     *
     * @return string[]
     */
    public function getFieldNames() // {{{
    {
        return array_keys($this->_fields);
    } // }}}
}
