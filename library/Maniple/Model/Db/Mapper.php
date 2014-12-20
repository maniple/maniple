<?php

/**
 * Data mapper based on Zend_Db package.
 *
 * Data mapper instances are designed to operate on database table(s), and in
 * consequence must be equipped with a table data gateway provider. Database
 * connection is not stored directly in mapper, but retrieved from gateway
 * provider instead.
 *
 * @package Maniple_Model_Db
 * @uses    Zend_Stdlib
 * @version 2013-11-30
 * @author  xemlock
 */
abstract class Maniple_Model_Db_Mapper
{
    /**
     * Table data gateway provider
     * @var Maniple_Model_Db_TableProvider
     */
    protected $_tableProvider;

    /**
     * Mapping between arbitrary keys and table classes
     * @var array
     */
    protected $_tableMap = array();

    /**
     * @param Maniple_Model_Db_TableProvider $tableProvider OPTIONAL
     */
    public function __construct(Maniple_Model_Db_TableProvider $tableProvider = null) // {{{
    {
        if ($tableProvider) {
            $this->setTableProvider($tableProvider);
        }
    } // }}}

    /**
     * Registers a table provider.
     *
     * @param  Maniple_Model_Db_TableProvider $tableProvider
     * @return Maniple_Model_Db_Mapper
     */
    public function setTableProvider(Maniple_Model_Db_TableProvider $tableProvider) // {{{
    {
        $this->_tableProvider = $tableProvider;
        return $this;
    } // }}}

    /**
     * @return Maniple_Model_Db_TableProvider
     * @throws Maniple_Model_Mapper_Exception_InvalidState
     */
    public function getTableProvider() // {{{
    {
        if (empty($this->_tableProvider)) {
            throw new Maniple_Model_Db_Exception_InvalidState(
                'Table provider is not registered with this mapper instance'
            );
        }
        return $this->_tableProvider;
    } // }}}

    /**
     * Returns database adapter associated with registered table provider.
     *
     * @return Zend_Db_Adapter_Abstract
     * @throws Maniple_Model_Mapper_Exception_InvalidState
     */
    public function getAdapter() // {{{
    {
        return $this->getTableProvider()->getAdapter();
    } // }}}

    /**
     * Retrieves from the registered table provider a Zend_Db_Table instance
     * corresponding to given table name.
     *
     * @param  string $tableName
     * @return Zend_Db_Table_Abstract
     * @throws Maniple_Model_Mapper_Exception_InvalidArgument
     */
    protected function _getTable($tableName) // {{{
    {
        if (isset($this->_tableMap[$tableName])) {
            $tableClass = $this->_tableMap[$tableName];
        } else {
            $tableClass = $tableName;
        }

        return $this->getTableProvider()->getTable($tableClass);
    } // }}}

    /**
     * Supported options:
     *   string|array exclude   list of columns to exclude from result,
     *                          matching is case-insensitive
     *   string prefix          alias columns using a given prefix
     *
     * @param  string|Zend_Db_Table $table
     * @param  array $options
     * @return array
     */
    protected function _getColumns($table, $options = null) // {{{
    {
        if (!$table instanceof Zend_Db_Table_Abstract) {
            $table = $this->_getTable($table);
        }

        $info = $table->info(Zend_Db_Table_Abstract::COLS);
        $cols = array_combine($info, $info);

        if (isset($options['exclude'])) {
            $exclude = array_filter((array) $options['exclude'], 'is_string');
            $exclude = array_flip(array_map('strtolower', $exclude));
            foreach ($cols as $key => $column) {
                if (isset($exclude[strtolower($key)])) {
                    unset($cols[$key]);
                }
            }
        }

        if (isset($options['prefix'])) {
            $cols = self::prefixColumns($options['prefix'], $cols);
        }

        return $cols;
    } // }}}

    /**
     * Creates an empty select object.
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
     * Fetch all records from given select object.
     *
     * Supported modifiers:
     * - index_by       name of a record's field whose value will be used as
     *                  a corresponding key in the result array
     * - map            callback applied on each retrieved record
     *
     * @param  Zend_Db_Select $select
     * @param  array $modifiers
     * @return array
     */
    protected function _fetchAll(Zend_Db_Select $select, array $modifiers = null) // {{{
    {
        if (isset($modifiers['map'])) {
            $map = $modifiers['map'];
            if (!$map instanceof Zend_Stdlib_CallbackHandler) {
                $map = new Zend_Stdlib_CallbackHandler($map);
            }
        } else {
            $map = null;
        }

        if (isset($modifiers['index_by'])) {
            $index_by = $modifiers['index_by'];

            $stmt = $select->query(Zend_Db::FETCH_ASSOC);
            $rows = array();

            while ($row = $stmt->fetch()) {
                if ($map) {
                    $row = $map->__invoke($row);
                }
                $rows[$row[$index_by]] = $row;
            }

        } else {
            $db = $select->getAdapter();
            $rows = $db->fetchAll($select, null, Zend_Db::FETCH_ASSOC);

            if ($map) {
                $rows = array_map(array($map, '__invoke'), $rows);
            }
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
     * @param  string $separator
     * @param  array $row
     * @return array
     */
    public static function splitKeys($separator, array $row) // {{{
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

    /**
     * Builds an aliased columns list from a prefix and column list.
     *
     * @param  string $prefix
     * @param  array $columns
     * @return array
     */
    public static function prefixColumns($prefix, array $columns) // {{{
    {
        $result = array();

        foreach ($columns as $column) {
            $result[$prefix . $column] = $column;
        }

        return $result;
    } // }}}
}
