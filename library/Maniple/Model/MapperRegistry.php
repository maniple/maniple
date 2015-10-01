<?php

class Maniple_Model_MapperRegistry
{
    /**
     * @var array
     */
    protected $_registry;

    /**
     * @param string $modelClass
     * @param $mapper
     * @return $this
     */
    public function registerMapper($modelClass, $mapper)
    {
        $this->_registry[$modelClass] = $mapper;
        return $this;
    }

    /**
     * @param string $modelClass
     * @return mixed
     * @throws Maniple_Model_MapperRegistry_Exception
     */
    public function getMapper($modelClass)
    {
        if (isset($this->_registry[$modelClass])) {
            return $this->_registry[$modelClass];
        }
        throw new Maniple_Model_MapperRegistry_Exception(
            sprintf("Mapper for model class '%s' is not registered", $modelClass)
        );
    }
}