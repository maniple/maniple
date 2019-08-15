<?php

class Maniple_SlugGenerator_DbTable extends Maniple_SlugGenerator_Db
{
    /**
     * @param Zend_Db_Table_Abstract $dbTable
     * @param string $colName
     */
    public function __construct(Zend_Db_Table_Abstract $table, $colName = 'slug')
    {
        $db = $table->getAdapter();
        $tableName = method_exists($table, 'getName') ? $table->getName() : $table->info(Zend_Db_Table::NAME);

        parent::__construct($db, $tableName, $colName);
    }
}
