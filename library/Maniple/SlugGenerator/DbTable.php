<?php

class Maniple_SlugGenerator_DbTable extends Maniple_SlugGenerator_Db
{
    /**
     * @param Zend_Db_Table_Abstract $dbTable
     * @param string $colName
     */
    public function __construct(Zend_Db_Table_Abstract $table, $colName = 'slug')
    {
        $this->setFromTable($table);
        $this->setColName($colName);
    }

    /**
     * @param Zend_Db_Table_Abstract $table
     * @return $this
     */
    public function setFromTable(Zend_Db_Table_Abstract $table)
    {
        $tableName = method_exists($table, 'getName') ? $table->getName() : $table->info(Zend_Db_Table::NAME);

        $this->_db = $table->getAdapter();
        $this->_tableName = $tableName;

        return $this;
    }
}