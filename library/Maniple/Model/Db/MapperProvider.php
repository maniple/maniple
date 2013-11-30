<?php

class Maniple_Model_Db_MapperProvider implements Maniple_Model_MapperProvider
{
    /**
     * @var Zend_Db_Adapter_Abstract
     */
    protected $_db;

    /**
     * @var Maniple_Model_Db_TableProvider
     */
    protected $_tableProvider;

    /**
     * @var array
     */
    protected $_mappers = array();

    /**
     * Zend_Db_Adapter_Abstract $db
     */
    public function __construct(Zend_Db_Adapter_Abstract $db)
    {
        $this->_tableProvider = new Maniple_Model_Db_TableProvider($db);
    }

    /**
     * Get registered table provider.
     *
     * @return Maniple_Model_Db_TableProvider
     */
    public function getTableProvider() // {{{
    {
        return $this->_tableProvider;
    } // }}}

    /**
     * @return Zend_Db_Adapter_Abstract
     * @throws Maniple_Model_Db_Exception_InvalidState
     */
    public function getAdapter() // {{{
    {
        if (empty($this->_db)) {
            throw new Maniple_Model_Db_Exception_InvalidState(
                'Database adapter is not configured'
            );
        }
        return $this->_db;
    } // }}}

    /**
     * @param  string $className
     * @return Maniple_Model_Db_Mapper
     * @throws Maniple_Model_Db_Exception_InvalidArgument
     */
    public function getMapper($className) // {{{
    {
        if (empty($this->_mappers[$className])) {
            $mapper = new $className($this->_tableProvider);
            if (!$mapper instanceof Maniple_Model_Db_Mapper) {
                throw new Maniple_Model_Db_Exception_InvalidArgument(sprintf(
                    "Mapper must be an instance of Maniple_Model_Db_Mapper, got '%s' instead.",
                    get_class($table)
                ));
            }
            $this->_mappers[$className] = $mapper;
        }
        return $this->_mappers[$className];
    } // }}}
}
