<?php

abstract class Maniple_Model_Db_TableMapper extends Maniple_Model_Db_Mapper
    implements Maniple_Model_RepositoryInterface
{
    /**
     * Class or name of the managed table.
     * @var string
     */
    protected $_table;

    /**
     * Build SELECT instance for use in {@link fetch()} and {@link fetchAll()}
     * methods.
     *
     * Override this method to provide more record retrieval related logic.
     *
     * @param  array $conditions
     * @param  array $modifiers
     * @return Maniple_Model_Db_Select
     */
    protected function _selectAll($conditions = null, $modifiers = null) // {{{
    {
        $table = $this->_getTable($this->_table);
        $select = $this->_createSelect($table);

        if ($conditions) {
            $select->conditions($conditions);
        }

        if ($modifiers) {
            $select->modifiers($modifiers);
        }

        return $select;
    } // }}}

    /**
     * Fetch all records from managed table matching given criteria.
     *
     * If an 'index_by' modifier is provided, it will be used as a column
     * name for indexing retrieved records.
     *
     * @param  array $conditions
     * @param  array $modifiers
     * @return array
     */
    public function fetchAll($conditions = null, $modifiers = null) // {{{
    {
        $select = $this->_selectAll($conditions, $modifiers);

        if (isset($modifiers['index_by'])) {
            $index_by = $modifiers['index_by'];
            return $this->_fetchAll($select, array('index_by' => $index_by));
        }

        return $this->_fetchAll($select);
    } // }}}

    /**
     * Fetch a single record from managed table matching given criteria.
     *
     * If a 'throw' modifier is provided, an exception will be throw if no
     * record is fetched. If the value of this modifier is a string, it will
     * be used as the exception's error message.
     *
     * @param  array $conditions
     * @param  array $modifiers
     * @return array|null
     * @throws Maniple_Model_Db_Exception_RecordNotFound
     */
    public function fetch($conditions = null, $modifiers = null) // {{{
    {
        $modifiers['limit'] = 1;

        foreach ($this->fetchAll($conditions, $modifiers) as $row) {
            return $row;
        }

        if (isset($modifiers['throw']) && $modifiers['throw']) {
            throw new Maniple_Model_Db_Exception_RecordNotFound(
                is_string($modifiers['throw'])
                    ? $modifiers['throw']
                    : 'No record matching the specified criteria found'
            );
        }

        return null;
    } // }}}

    /**
     * Create Zend_Db_Table row for record existing in the database.
     * For internal operations only.
     *
     * This method is particularily useful when one wants to operate on
     * records retrieved using a Zend_Db_Select instance instead of
     * Zend_Db_Table_Select.
     *
     * @param string $table
     * @param array $data
     * @return Zend_Db_Table_Row
     */
    protected function _toTableRow($tableName, array $data) // {{{
    {
        $table = $this->_getTable($tableName);
        $rowClass = $table->getRowClass();

        return new $rowClass(array(
            'table'    => $table,
            'data'     => $data,
            'readOnly' => false,
            'stored'   => true, // use given data as clean data, i.e. treat
                                // this record as stored in the database
        ));
    } // }}}
}
