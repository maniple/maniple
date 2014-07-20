<?php

interface Maniple_Search_DocumentInterface
{
    /**
     * Adds field to document.
     *
     * @param  Maniple_Search_FieldInterface $field
     * @return mixed
     */
    public function addField(Maniple_Search_FieldInterface $field);

    /**
     * Retrieves field corresponding to a given name.
     *
     * @param  string $name
     * @return Maniple_Search_DocumentInterface|null
     */
    public function getField($name);

    /**
     * Retrieves list of all fields in this document.
     *
     * @return Maniple_Search_FieldInterface[]
     */
    public function getFields();

    /**
     * Retrieves list of names of all fields present in this document.
     *
     * @return string[]
     */
    public function getFieldNames();
}
