<?php

class Maniple_SlugGenerator_Db extends Maniple_SlugGenerator_Abstract
{
    /**
     * @var Zend_Db_Adapter_Abstract
     */
    protected $_db;

    /**
     * @var string
     */
    protected $_tableName;

    /**
     * @var string
     */
    protected $_colName;

    /**
     * @param Zend_Db_Adapter_Abstract $db
     * @param string $tableName
     * @param string $colName
     */
    public function __construct(Zend_Db_Adapter_Abstract $db, $tableName, $colName = 'slug')
    {
        $this->_db = $db;
        $this->setTableName($tableName);
        $this->setColName($colName);
    }

    /**
     * @param string $slug
     * @return bool
     */
    public function slugExists($slug)
    {
        $select = $this->_db->select();
        $select->from(
            array('tbl' => $this->_tableName),
            array('cnt' => new Zend_Db_Expr('COUNT(1)'))
        );
        $select->where($this->_db->quoteIdentifier($this->_colName) . ' = ?', (string) $slug);

        $count = (int) $select->query()->fetchColumn('cnt');
        return (bool) $count;
    }

    /**
     * @param Zend_Db_Adapter_Abstract $db
     * @return $this
     */
    public function setDbAdapter(Zend_Db_Adapter_Abstract $db)
    {
        $this->_db = $db;
        return $this;
    }

    /**
     * @return Zend_Db_Adapter_Abstract
     */
    public function getDbAdapter()
    {
        return $this->_db;
    }

    /**
     * @param string $tableName
     * @return $this
     */
    public function setTableName($tableName)
    {
        $this->_tableName = (string) $tableName;
        return $this;
    }

    /**
     * @return string
     */
    public function getTableName()
    {
        return $this->_tableName;
    }

    /**
     * @param string $colName
     * @return $this
     */
    public function setColName($colName)
    {
        $this->_colName = (string) $colName;
        return $this;
    }

    /**
     * @return string
     */
    public function getColName()
    {
        return $this->_colName;
    }
}