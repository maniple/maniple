<?php

abstract class Maniple_Model_Db_Mapper
{
    /**
     * @var Zend_Db_Adapter_Abstract
     * @deprecated
     */
    protected $_db;

    /**
     * Table provider
     * @var Maniple_Model_Db_TableProvider
     */
    protected $_tableProvider;

    /**
     * Mapping between arbitrary table keys and class names / instances
     * @var array
     */
    protected $_tables;

    /**
     * @param Maniple_Model_Db_TableProvider $db
     */
    public function __construct($tableProvider = null) // {{{
    {
        if ($tableProvider instanceof Maniple_Model_Db_TableProvider) {
            $this->setTableProvider($tableProvider);
        } elseif ($tableProvider instanceof Zend_Db_Adapter_Abstract) {
            $this->setAdapter($tableProvider);
        }
    } // }}}

    /**
     * Registers a table provider.
     */
    public function setTableProvider(Maniple_Model_Db_TableProvider $tableProvider) // {{{
    {
        $this->_tableProvider = $tableProvider;
        return $this;
    } // }}}

    /**
     * Registers a database adapter.
     *
     * @deprecated
     * @param  Zend_Db_Adapter_Abstract $db
     * @return Maniple_Model_Db_Abstract
     */
    public function setAdapter(Zend_Db_Adapter_Abstract $db) // {{{
    {
        $this->_db = $db;
        return $this;
    } // }}}

    /**
     * Returns currently registered database adapter.
     *
     * @return Zend_Db_Adapter_Abstract
     * @throws Maniple_Model_Mapper_Exception_InvalidState
     */
    public function getAdapter() // {{{
    {
        if (empty($this->_db) && $this->_tableProvider) {
            return $this->_tableProvider->getAdapter();
        }

        if (!$this->_db instanceof Zend_Db_Adapter_Abstract) {
            throw new Maniple_Model_Db_Exception_InvalidState(
                'Database adapter is not registered with this mapper object'
            );
        }
        return $this->_db;
    } // }}}

    /**
     * Creates or retrieves a Zend_Db_Table instance.
     *
     * @param  string $name
     * @return Zend_Db_Table_Abstract
     * @throws Maniple_Model_Mapper_Exception_InvalidArgument
     */
    protected function _getTable($name) // {{{
    {
        if (!isset($this->_tables[$name])) {
            throw new Maniple_Model_Db_Exception_InvalidArgument(sprintf(
                'Invalid table name: %s', $name
            ));
        }
        if (is_string($this->_tables[$name])) {
            $tableClass = $this->_tables[$name];

            // maintain backwards compatibility
            if (isset($this->_tableProvider)) {
                $table = $this->_tableProvider->getTable($tableClass);
            } else {
                $db = $this->getAdapter();
                $table = Zefram_Db::getTable($tableClass, $db);
            }

            $this->_tables[$name] = $table;
        }
        return $this->_tables[$name];
    } // }}}

    /**
     * Creates an empty SELECT query.
     *
     * @param  array|string|Zend_Db_Table $table
     * @param  array|string $cols
     * @param  string $schema
     * @return Maniple_Model_Db_Select
     */
    protected function _createSelect($table = null, $cols = '*', $schema = null) // {{{
    {
        $select = new Maniple_Model_Db_Select($this->getAdapter());

        if (null !== $table) {
            $select->from($table, $cols, $schema);
        }

        return $select;
    } // }}}

    /**
     * @param  Zend_Db_Select $select
     * @param  array $modifiers
     * @return array
     */
    protected function _fetchAll(Zend_Db_Select $select, array $modifiers = null) // {{{
    {
        if (isset($modifiers['index_by'])) {
            $index_by = $modifiers['index_by'];

            $stmt = $select->query(Zend_Db::FETCH_ASSOC);
            $rows = array();

            while ($row = $stmt->fetch()) {
                $rows[$row[$index_by]] = $row;
            }

        } else {
            $db = $select->getAdapter();
            $rows = $db->fetchAll($select, null, Zend_Db::FETCH_ASSOC);
        }

        return $rows;
    } // }}}

    /**
     * @param  Zend_Db_Select $select
     * @return array
     */
    protected function _fetch(Zend_Db_Select $select) // {{{
    {
        $db = $select->getAdapter();
        return $db->fetchRow($select, null, Zend_Db::FETCH_ASSOC);
    } // }}}


    /**
     * Combine record columns into a sub-record. If all subrecord columns
     * are equal to NULL (which can be typically a result of LEFT JOIN),
     * the subrecord will be replaced with a NULL value.
     *
     * @param  array $row
     * @param  string $separator
     * @return array
     */
    public static function splitKeys(array $row, $separator) // {{{
    {
        $result = array();

        foreach ($row as $key => $value) {
            if (false !== strpos($key, $separator)) {
                $parts = explode($separator, $key);
                $pkey = array_pop($parts);
                $ptr = &$result;

                while (null !== ($part = array_shift($parts))) {
                    if (!isset($ptr[$part]) || !is_array($ptr[$part])) {
                        $ptr[$part] = array();
                    }
                    $ptr = &$ptr[$part];
                }

                $ptr[$pkey] = $value;
                unset($ptr);

            } else {
                $result[$key] = $value;
            }
        }

        // replace all null arrays with a single null
        $result = self::nullifyEmpty($result);

        return (array) $result;
    } // }}}

    /**
     * Recursively replace all-NULL arrays with NULL values.
     *
     * @param array $array
     * @return null|array
     */
    public static function nullifyEmpty(array $array) // {{{
    {
        $nulls = 0;

        foreach ($array as $key => $value) {
            if (is_array($value)) {
                $array[$key] = $value = self::nullifyEmpty($value);
            }
            if (null === $value) {
                ++$nulls;
            }
        }

        if ($nulls === count($array)) {
            return null;
        }

        return $array;
    } // }}}

    /**
     * Replace key prefixes using given replacement map.
     *
     * This is particularly useful when translating search conditions
     * involving JOIN arrays from object-like notation.
     *
     * Order of elements in resulting array is undefined.
     *
     * @param  array $array
     * @param  array $replacements
     * @return array
     */
    public static function replaceKeyPrefixes(array $array, array $replacements) // {{{
    {
        foreach (array_keys($array) as $key) {
            foreach ($replacements as $from => $to) {
                if (!strncmp($key, $from, strlen($from))) {
                    $rkey = $to . substr($key, strlen($from));
                    $array[$rkey] = $array[$key];
                    unset($array[$key]);
                    break; // single replacement only
                }
            }
        }
        return $array;
    } // }}}
}
