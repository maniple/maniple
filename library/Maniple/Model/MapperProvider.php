<?php

interface Maniple_Model_MapperProvider
{
    /**
     * @param string $name
     */
    public function getMapper($name);
}
