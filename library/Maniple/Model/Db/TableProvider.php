<?php

class Maniple_Model_Db_TableProvider
{
    /**
     * @var Zend_Db_Adapter_Abstract
     */
    protected $_db;

    /**
     * @var string
     */
    protected $_tablePrefix;

    /**
     * Zend_Db_Table_Abstract instances
     * @var array
     */
    protected $_tables = array();

    /**
     * Zend_Db_Adapter_Abstract $db
     */
    public function __construct(Zend_Db_Adapter_Abstract $db) // {{{
    {
        $this->_db = $db;
    } // }}}

    /**
     * @return Zend_Db_Adapter_Abstract
     * @throws Maniple_Db_Exception_InvalidState
     */
    public function getAdapter() // {{{
    {
        if (empty($this->_db)) {
            throw new Maniple_Db_Exception_InvalidState(
                'Database adapter is not configured'
            );
        }
        return $this->_db;
    } // }}}

    /**
     * @return string
     */
    public function getTablePrefix() // {{{
    {
        return $this->_tablePrefix;
    } // }}}

    /**
     * @param  string $tablePrefix
     * @return Maniple_Model_Db_TableProvider
     */
    public function setTablePrefix($tablePrefix) // {{{
    {
        $this->_tablePrefix = strval($tablePrefix);
        return $this;
    } // }}}

    /**
     * @param  string $className
     * @return Zend_Db_Table_Abstract
     * @throws Maniple_Model_Db_Exception_InvalidArgument
     */
    public function getTable($className) // {{{
    {
        if (empty($this->_tables[$className])) {
            if (class_exists($className)) {
                $table = new $className($this->getAdapter());
                if (!$table instanceof Zend_Db_Table_Abstract) {
                    throw new Maniple_Model_Db_Exception_InvalidArgument(sprintf(
                        "Table must be an instance of Zend_Db_Table_Abstract, got '%s' instead.",
                        get_class($table)
                    ));
                }
            } else {
                // assume className is not really a class name, but rather
                // a table name, instantiate a generic table instead
                $table = new Zefram_Db_Table(array(
                    'db' => $this->getAdapter(),
                    'name' => $className,
                ));
            }
            if ($this->_tablePrefix) {
                $table->setOptions(array(
                    'name' => $this->_tablePrefix . $table->info(Zend_Db_Table_Abstract::NAME),
                ));
            }
            $this->_tables[$className] = $table;
        }
        return $this->_tables[$className];
    } // }}}
}
