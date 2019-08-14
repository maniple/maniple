<?php

class Maniple_Filter_VarExport implements Zend_Filter_Interface
{
    /**
     * @var int
     */
    protected $_indent = 4;

    /**
     * @param mixed $value
     * @return string
     * @throws Zend_Filter_Exception
     */
    public function filter($value)
    {
        return $this->_varExport($value);
    }

    /**
     * @return int
     */
    public function getIndent()
    {
        return $this->_indent;
    }

    /**
     * @param int $indent
     * @return $this
     */
    public function setIndent($indent)
    {
        $this->_indent = $indent;
        return $this;
    }

    /**
     * @param mixed $value
     * @param int $depth
     * @return string
     * @throws Zend_Filter_Exception
     */
    protected function _varExport($value, $depth = 0)
    {
        switch (true) {
            case is_string($value):
                return "'" . addcslashes($value, "'") . "'";

            case is_bool($value):
                return $value ? 'true' : 'false';

            case is_int($value) || is_float($value):
                return var_export($value);

            case $value === null:
                return 'null';

            case is_array($value):
                $str = "array(\n";

                $keyLength = 0;
                $previousIsArray = false;

                foreach ($value as $k => $v) {
                    $keyLength = max($keyLength, strlen(var_export($k, 1)));
                }

                $indent = str_repeat(' ', $this->_indent * $depth);
                $childIndent = str_repeat(' ', $this->_indent * ($depth + 1));

                foreach ($value as $k => $v) {
                    $strKey = var_export($k, 1);
                    $strPad = $previousIsArray
                        ? ''
                        : str_repeat(' ', max(0, $keyLength - strlen($strKey)));

                    $str .= $childIndent . $strKey . $strPad . ' => ' . $this->_varExport($v, $depth + 1) . ",\n";

                    $previousIsArray = is_array($v);
                }
                $str .= $indent . ")";
                return $str;
        }

        throw new Zend_Filter_Exception(
            sprintf('Unserializable value type: %s', is_object($value) ? get_class($value) : gettype($value))
        );
    }

    /**
     * @var Maniple_Filter_VarExport
     */
    protected static $_instance;

    public static function filterStatic($value, array $options = array())
    {
        if (!self::$_instance) {
            self::$_instance = new self();
        }

        self::$_instance->setIndent(isset($options['indent']) ? (int) $options['indent'] : 4);

        return self::$_instance->filter($value);
    }
}
