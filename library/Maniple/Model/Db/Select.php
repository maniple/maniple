<?php

class Maniple_Model_Db_Select extends Zefram_Db_Select
{
    const OP_GT        = '>';
    const OP_LT        = '<';
    const OP_GTE       = '>=';
    const OP_LTE       = '<=';
    const OP_NOT       = '<>';
    const OP_EQ        = '=';
    const OP_LIKE      = 'LIKE';
    const OP_NOT_LIKE  = 'NOT LIKE';

    const SORT_ASC     = 'ASC';
    const SORT_DESC    = 'DESC';

    /**
     * @var string
     */
    protected $_defaultCorrelation;

    /**
     * @deprecated
     */
    public function criteria(array $criteria, $defaultCorrelation = null) // {{{
    {
        return $this->conditions($criteria, $defaultCorrelation);
    } // }}}

    /**
     * Add criteria to WHERE clause.
     *
     * @param array $conditions
     * @param string $defaultCorrelation OPTIONAL
     * @return Maniple_Model_DbMapper_Select
     */
    public function conditions(array $conditions, $defaultCorrelation = null) // {{{
    {
        if (is_null($defaultCorrelation)) {
            $defaultCorrelation = $this->_defaultCorrelation;
        }
        $where = $this->translateConditions($this->getAdapter(), $conditions, $defaultCorrelation);
        return $this->where($where);
    } // }}}

    public function sort($sort, $defaultCorrelation = null) // {{{
    {
        if (is_null($defaultCorrelation)) {
            $defaultCorrelation = $this->_defaultCorrelation;
        }
        $order = self::translateSort($this->getAdapter(), $sort, $defaultCorrelation);
        return $this->order($order);
    } // }}}

    public function defaultCorrelation($defaultCorrelation = null) // {{{
    {
        if (empty($defaultCorrelation)) {
            $this->_defaultCorrelation = null;
        } else {
            $this->_defaultCorrelation = (string) $defaultCorrelation;
        }
        return $this;
    } // }}}

    /**
     * Set record retrieval modifiers.
     *
     * Currently supported modifiers:
     * sort, order, limit, offset, page, rows_per_page
     *
     * @param  array $modifiers
     * @return Maniple_Model_Db_Select
     */
    public function modifiers(array $modifiers) // {{{
    {
        foreach ($modifiers as $key => $value) {
            switch ($key) {
                case 'sort':
                    $this->sort($value);
                    break;

                case 'order':
                    $this->order($value);
                    break;

                case 'limit':
                    if (isset($modifiers['offset'])) {
                        $this->limit($value, $modifiers['offset']);
                    } else {
                        $this->limit($value);
                    }
                    break;

                case 'page':
                    if (isset($modifiers['rows_per_page'])) {
                        $this->limitPage($value, $modifiers['rows_per_page']);
                    } else {
                        $this->limitPage($value, 1);
                    }
                    break;
            }
        }
    } // }}}

    /**
     * Translate mapper conditions to WHERE clause understood by Zend_Db_Select.
     *
     * @param  Zend_Db_Adapter_Abstract $db
     * @param  array $conditions
     * @param  string $defaultCorrelation OPTIONAL
     * @return array
     */
    public static function translateConditions(Zend_Db_Adapter_Abstract $db, array $conditions, $defaultCorrelation = null) // {{{
    {
        $where = array();

        foreach ($conditions as $column => $value) {
            $symbol = strrchr($column, '.');

            switch ($symbol) {
                case '.gt':
                    $op = self::OP_GT;
                    break;

                case '.lt':
                    $op = self::OP_LT;
                    break;

                case '.gte':
                    $op = self::OP_GTE;
                    break;

                case '.lte':
                    $op = self::OP_LTE;
                    break;

                case '.not':
                case '.neq':
                    $op = self::OP_NOT;
                    break;

                case '.eq':
                case '.eql':
                    $op = self::OP_EQ;
                    break;

                case '.like':
                    $op = self::OP_LIKE;
                    break;

                case '.ilike':
                    $op = self::OP_ILIKE;
                    break;

                case '.not_like':
                    $op = self::OP_NOT_LIKE;
                    break;

                default:
                    $symbol = null;
                    $op = self::OP_EQ;
                    break;
            }

            // strip off op symbol from column name
            if (null !== $symbol) {
                $column = substr($column, 0, -strlen($symbol));
            }

            // check for .nocase suffix that initializes case-insensitive match
            // In case-insensitive mode both operands are lowercased.
            // Fortunately LOWER() function is available in all major DBMS,
            // unfortunately though, some implementations (namely, PostgreSQL)
            // are strict in terms of parameter types, which may result in
            // a database error if LOWER() is applied to a non-string value.
            // In other words, use with caution.
            if ('.nocase' === substr($column, -7)) {
                $column = substr($column, 0, -7);
                $nocase = true;
            } else {
                $nocase = false;
            }

            // if default correlation name was given prepend it to the column
            // name if it does not already contain a correlation name
            if (false === strpos($column, '.') && null !== $defaultCorrelation) {
                $column = $defaultCorrelation . '.' . $column;
            }

            $quoted_column = $db->quoteIdentifier($column);

            if ($nocase) {
                $quoted_column = 'LOWER(' . $quoted_column . ')';
            }

            if (null === $value) {
                // NULL matching
                switch ($op) {
                    case self::OP_EQ:
                        $where[] = $quoted_column . ' IS NULL';
                        break;

                    case self::OP_NOT:
                        $where[] = $quoted_column . ' IS NOT NULL';
                        break;

                    default:
                        throw new Maniple_Model_DbMapper_Exception_InvalidArgument(
                            'NULL value can be tested for equality/inequality only'
                        );
                }
            } elseif (is_array($value)) {
                // filter-out NULLs as Zend_Db_Adapter_Abstract::quote() handles
                // them improperly (renders empty string instead of 'NULL').
                foreach ($value as $key => $val) {
                    if (null === $val) {
                        unset($value[$key]);
                    }
                }
                if ($nocase) {
                    foreach ($value as $key => $val) {
                        $value[$key] = new Zend_Db_Expr(sprintf(
                            'LOWER(%s)', $db->quote(strval($val))
                        ));
                    }
                }
                switch ($op) {
                    case self::OP_EQ:
                        if (count($value)) {
                            $where[$quoted_column . ' IN (?)'] = $value;
                        } else {
                            // this shouldn't match anything
                            $where[] = $quoted_column . ' IN (NULL)';
                        }
                        break;

                    case self::OP_NOT:
                        if (count($value)) {
                            $where[$quoted_column . ' NOT IN (?)'] = $value;
                        } else {
                            // this shouldn't match anything
                            $where[] = $quoted_column . ' NOT IN (NULL)';
                        }
                        break;

                    default:
                        throw new Maniple_Model_DbMapper_Exception_InvalidArgument(
                            'A set of values can be tested for inclusion/exclusion only'
                        );
                }
            } elseif ($nocase) {
                $where[$quoted_column . ' ' . $op . ' LOWER(?)'] = $value;
            } else {
                $where[$quoted_column . ' ' . $op . ' ?'] = $value;
            }
        }

        return $where;
    } // }}}

    /**
     * Translate mapper ordering to a ORDER clause understood by Zend_Db_Select.
     *
     * @param  Zend_Db_Adapter_Abstract $db
     * @param  string|array $sort
     * @param  string $defaultCorrelation OPTIONAL
     * @return array
     */
    public static function translateSort(Zend_Db_Adapter_Abstract $db, $sort, $defaultCorrelation = null) // {{{
    {
        $order = array();

        foreach ((array) $sort as $column) {
            $nocase = false;
            $column = (string) $column;
            $symbol = strrchr($column, '.');

            switch ($symbol) {
                case '.asc':
                    $direction = self::SORT_ASC;
                    break;

                case '.desc':
                    $direction = self::SORT_DESC;
                    break;

                default:
                    $direction = self::SORT_ASC;
                    $symbol = null;
                    break;
            }

            if (null !== $symbol) {
                $column = substr($column, 0, -strlen($symbol));
            }

            if ('.nocase' === substr($column, -7)) {
                $nocase = true;
                $column = substr($column, 0, -7);
            }

            // if default correlation name was given, prepend it to the
            // column name, provided that the latter does not already
            // contain a correlation name
            if (false === strpos($column, '.') && null !== $defaultCorrelation) {
                $column = $defaultCorrelation . '.' . $column;
            }

            if ($nocase) {
                // If a column contains parentheses, Zend_Db_Select recognizes
                // it as an expression. See:
                // http://framework.zend.com/manual/1.12/en/zend.db.select.html
                $order[] = 'LOWER(' . $db->quoteIdentifier($column) . ') ' . $direction;
            } else {
                // do not quote identifier, as it is done in a crude
                // way by Zend_Db_Select::_renderOrder()
                $order[] = $column . ' ' . $direction;
            }
        }

        return $order;
    } // }}}
}
