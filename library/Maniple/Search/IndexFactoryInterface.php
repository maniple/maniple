<?php

interface Maniple_Search_IndexFactoryInterface
{
    /**
     * @param  string $index
     * @return Maniple_Search_IndexInterface|null
     */
    public function getIndex($index);

    /**
     * @param  string $index
     * @param  array $options OPTIONAL
     * @return Maniple_Search_IndexInterface
     */
    public function createIndex($index, array $options = null);
}
