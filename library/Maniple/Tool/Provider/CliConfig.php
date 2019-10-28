<?php

class Maniple_Tool_Provider_CliConfig extends Zend_Tool_Framework_Provider_Abstract
{
    const className = __CLASS__;

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

        $php = Maniple_Filter_VarExport::filterStatic($config);

        file_put_contents($this->_getConfigPath(), '<?php return ' . $php . ";\n");
    }

    protected function _loadConfig()
    {
        if (is_file($this->_getConfigPath())) {
            $config = (array) require $this->_getConfigPath();
        } else {
            $config = array();
        }
        return $config;
    }

    protected function _getConfigPath()
    {
        return APPLICATION_PATH . '/configs/cli.config.php';
    }
}
