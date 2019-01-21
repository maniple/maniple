<?php

class Maniple_Tool_Provider_CliConfig extends Zend_Tool_Framework_Provider_Abstract
{
    const className = __CLASS__;

    const CONFIG_PATH = './application/configs/cli.config.php';

    /**
     * @param string $key
     * @param string $value
     */
    public function set($key, $value)
    {
        $config = $this->_loadConfig();

        if ($value === 'true') {
            $value = true;
        } elseif ($value === 'false') {
            $value = false;
        } elseif ($value === 'null') {
            $value = null;
        } elseif (strlen($value) && is_numeric($value)) {
            $value = +$value;
        } else {
            $value = strval($value);
        }

        $ptr = &$config;
        $keySegments = preg_split('/(?<!\\\\)\./', $key);
        $currentPath = array();

        while (count($keySegments)) {
            $keySegment = array_shift($keySegments);
            $keySegment = str_replace('\\.', '.', $keySegment);

            if (!count($keySegments)) {
                $ptr[$keySegment] = $value;
                break;
            }

            $currentPath[] = $keySegment;

            if (!isset($ptr[$keySegment])) {
                $ptr[$keySegment] = array();
            } elseif (!is_array($ptr[$keySegment])) {
                throw new Exception('Unable to add key to a scalar value at ' . implode('.', $currentPath));
            }

            $ptr = &$ptr[$keySegment];
        }

        $php = $this->_dumpValue($config);

        file_put_contents(self::CONFIG_PATH, '<?php return ' . $php . ";\n");
    }

    protected function _loadConfig()
    {
        if (is_file(self::CONFIG_PATH)) {
            $config = (array) require self::CONFIG_PATH;
        } else {
            $config = array();
        }
        return $config;
    }

    protected function _dumpValue($value, $indent = '')
    {
        if (is_scalar($value) || $value === null) {
            return var_export($value, 1);
        } elseif (is_array($value)) {
            $str = "array(\n";
            foreach ($value as $k => $v) {
                $str .= $indent . '    ' . var_export($k, 1) . ' => ' . $this->_dumpValue($v, $indent . '    ') . ",\n";
            }
            $str .= $indent . ")";
            return $str;
        } else {
            throw new Exception(sprintf('Unserializable value: %s', is_object($value) ? get_class($value) : gettype($value)));
        }
    }
}
