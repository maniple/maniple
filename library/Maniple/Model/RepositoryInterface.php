<?php

interface Maniple_Model_RepositoryInterface
{
    /**
     * Fetch a single record matching given criteria.
     *
     * @param  array $conditions
     * @param  array $modifiers
     * @return mixed
     */
    public function fetch($conditions = null, $modifiers = null);

    /**
     * Fetch all records matching given criteria.
     *
     * @param  array $conditions
     * @param  array $modifiers
     * @return midex
     */
    public function fetchAll($conditions = null, $modifiers = null);
}
