<?php

interface Maniple_Search_IndexFactoryInterface
{
    /**
     * @param  string $name
     * @return Maniple_Search_IndexInterface|null
     */
    public function getIndex($name);

    /**
     * @param  string $name
     * @param  array $options OPTIONAL
     * @return Maniple_Search_IndexInterface
     */
    public function createIndex($name);
}
