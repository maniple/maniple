<?php

interface Maniple_Search_IndexFactoryInterface
{
    /**
     * @param  string $name
     * @return Maniple_Search_IndexInterface
     */
    public function getIndex($name);
}
