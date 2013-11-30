<?php

interface Maniple_Model_MapperProviderInterface
{
    /**
     * @param  string $name
     * @return mixed
     */
    public function getMapper($name);
}
