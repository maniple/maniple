<?php

class Maniple_SlugGenerator_DbTable extends Maniple_SlugGenerator_Abstract
{
    /**
     * @var Zend_Db_Table_Abstract
     */
    protected $_dbTable;

    /**
     * @var string
     */
    protected $_colName;

    /**
     * @param Zend_Db_Table_Abstract $dbTable
     * @param string $colName
     */
    public function __construct(Zend_Db_Table_Abstract $dbTable, $colName = 'slug')
    {
        $this->_dbTable = $dbTable;
        $this->_colName = $colName;
    }

    /**
     * @param string $slug
     * @return bool
     */
    public function slugExists($slug)
    {
        $table = $this->_dbTable;
        $tableName = method_exists($table, 'getName') ? $table->getName() : $table->info(Zend_Db_Table::NAME);

        $db = $table->getAdapter();

        $select = $db->select();
        $select->from(
            array('tbl' => $tableName),
            array('cnt' => new Zend_Db_Expr('COUNT(1)'))
        );
        $select->where($db->quoteIdentifier($this->_colName) . ' = ?', (string) $slug);

        $count = (int) $select->query()->fetchColumn('cnt');
        return (bool) $count;
    }

}