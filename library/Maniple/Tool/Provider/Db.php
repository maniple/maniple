<?php

class Maniple_Tool_Provider_Db extends Maniple_Tool_Provider_Abstract
{
    const className = __CLASS__;

    /**
     * @var Zefram_Db
     */
    protected $_db;

    public function dumpAction()
    {
        $path = realpath(APPLICATION_PATH . '/../data/') . '/dumps';
        @mkdir($path, 0755, true);

        $config = $this->_getDbAdapterConfig();
        $adapter = self::getDbAdapterName($config['adapter']);
        switch ($adapter) {
            case 'Mysql':
                require_once __DIR__ . '/Db/DumpMysql.php';
                $output = Maniple_Tool_Provider_Db_DumpMysql::run($config, $path);
                break;

            case 'Pgsql':
                require_once __DIR__ . '/Db/DumpPgsql.php';
                $output = Maniple_Tool_Provider_Db_DumpPgsql::run($config, $path);
                break;

            default:
                throw new RuntimeException('Unsupported db adapter: ' . $adapter);
        }

        if (!$output) {
            throw new Zend_Tool_Framework_Provider_Exception("No output generated");
        }

        // gzip removes original file, which is what we want here
        exec("gzip " . escapeshellarg($output), $lines, $error);
        if ($error) {
            throw new Zend_Tool_Framework_Provider_Exception(implode("\n", $lines));
        }

        echo implode("\n", $lines);
        echo "Compressed dump written to ", $output . '.tgz', "\n\n";
    }

    /**
     * Get db adapter instance
     *
     * @return Zend_Db_Adapter_Abstract
     * @throws Zend_Application_Bootstrap_Exception
     */
    protected function _getDbAdapterConfig()
    {
        $dbResource = $this->_getApplication()->getBootstrap()->getPluginResource('Db');
        return $dbResource->getOptions();
    }

    /**
     * Get matching adapter name from input string
     *
     * @param string $name
     * @return string
     */
    public static function getDbAdapterName($name)
    {
        $name = strtolower($name);

        if (($pos = strrpos($name, '_')) !== false) {
            $name = substr($name, $pos + 1);
        }

        switch ($name) {
            case 'mysqli':
            case 'mysql':
                return 'Mysql';

            case 'oci':
            case 'oracle':
                return 'Oracle';

            case 'mssql':
            case 'sqlsrv':
            case 'sqlserver':
                return 'Sqlsrv';

            case 'ids':
            case 'informix':
                return 'Informix';

            case 'pgsql':
            case 'postgres':
            case 'postgresql':
                return 'Pgsql';
        }

        return ucfirst($name);
    }
}
