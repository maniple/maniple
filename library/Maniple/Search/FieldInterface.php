<?php

interface Maniple_Search_FieldInterface
{
    /**
     * Retrieves field name.
     *
     * @return string
     */
    public function getName();

    /**
     * Retrieves field value.
     *
     * @return mixed
     */
    public function getValue();

    /**
     * Is this field tokenizable?
     *
     * @return bool
     */
    public function isTokenizable();

    /**
     * Is this field unique?
     *
     * @return bool
     */
    public function isUnique();
}
