<?php

class Maniple_Tool_Provider_Db extends Maniple_Tool_Provider_Abstract
{
    const className = __CLASS__;

    /**
     * @var Zefram_Db
     */
    protected $_db;

    public function dumpAction($excludeTables = null)
    {
        $path = realpath(APPLICATION_PATH . '/../data/') . '/db/dumps';
        @mkdir($path, 0755, true);

        $config = $this->getDbAdapterConfig();
        $adapter = self::getDbAdapterName($config['adapter']);
        switch ($adapter) {
            case 'Mysql':
                require_once __DIR__ . '/Db/DumpMysql.php';
                Maniple_Tool_Provider_Db_DumpMysql::run($config, $path);
                return;

            case 'Pgsql':
                require_once __DIR__ . '/Db/DumpPgsql.php';
                Maniple_Tool_Provider_Db_DumpPgsql::run($config, $path);
                return;

            default:
                throw new RuntimeException('Unrecognized db adapter: ' . $adapter);
        }
    }

    /**
     * Get db adapter instance
     *
     * @return Zend_Db_Adapter_Abstract
     * @throws Zend_Application_Bootstrap_Exception
     */
    public function getDbAdapterConfig()
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
