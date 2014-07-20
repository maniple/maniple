<?php

interface Maniple_Search_IndexableInterface
{
    /**
     * Add tokenizable string value.
     *
     * @param  string $name
     * @param  string $value
     * @return mixed
     */
    public function addField($name, $value);

    /**
     * Add attribute, i.e. non-tokenizable value.
     *
     * @param  string $name
     * @param  mixed $value
     * @return mixed
     */
    public function addAttr($name, $value);

    /**
     * Retrieves array of tokenizable string values.
     *
     * @return array
     */
    public function getFields();

    /**
     * Retrieves array of attributes.
     *
     * @return array
     */
    public function getAttrs();
}
