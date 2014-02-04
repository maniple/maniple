<?php

class Maniple_Model_Db_TableProvider extends Zefram_Db_Table_Provider
{
    /**
     * Zend_Db_Table_Abstract instances
     * @var array
     */
    protected $_tables = array();

    /**
     * @param  string $className
     * @return Zend_Db_Table_Abstract
     * @throws Maniple_Model_Db_Exception_InvalidArgument
     */
    public function getTable($className) // {{{
    {
        if (strpos($className, '.') !== false) {
            list($moduleName, $tableName) = explode('.', $className, 2);
            $className = ucfirst($moduleName) . '_Model_DbTable_' . ucfirst($tableName);
        }

        if (empty($this->_tables[$className])) {
            $this->_tables[$className] = parent::getTable($className, $this->getAdapter());
        }
        return $this->_tables[$className];
    } // }}}
}
