<?php

interface Maniple_Search_IndexInterface
{
    /**
     * @param  mixed $query
     * @param  int $limit OPTIONAL
     * @param  int $offset OPTIONAL
     * @return Maniple_Search_SearchResultsInterface
     */
    public function search($query, $limit = null, $offset = null);
}
