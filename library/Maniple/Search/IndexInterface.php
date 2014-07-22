<?php

interface Maniple_Search_IndexInterface
{
    /**
     * @param  string $query
     * @param  int $limit OPTIONAL
     * @param  int $offset OPTIONAL
     * @return Maniple_Search_SearchResultsInterface
     */
    public function search($query, $limit = null, $offset = null);

    /**
     * @param  string|Maniple_Search_FieldInterface $field
     * @param  string $value
     * @return string
     */
    public function getFieldQuery($field, $value = null);
}
