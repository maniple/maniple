<?php

class Maniple_Tool_Provider_Schema extends Maniple_Tool_Provider_Abstract
{
    const className = __CLASS__;

    /**
     * @var Zefram_Db
     */
    protected $_db;

    /**
     * @param string $module
     * @param string $adapter
     */
    public function listAction($module, $adapter = null)
    {
        $schemas = $this->_getModuleSchemas($module);

        if ($adapter === null) {
            $adapterClass = get_class($this->_getDbAdapter());
            $parts = explode('_', $adapterClass);

            $schemaType = self::getSchemaName(end($parts));
        } else {
            $schemaType = self::getSchemaName($adapter);
        }

        print_r($schemaType);
        print_r($schemas);

        if (empty($schemas[$schemaType])) {
            echo 'No schema found for adapter: ', $schemaType, "\n";
            return;
        }
    }

    public function installAction($module)
    {
        $install = $this->_prepareInstall($module);

        if (empty($install)) {
            echo 'No schemas to install.', "\n";
            return;
        }

        $db = $this->_getDbAdapter();

        foreach ($install as $id => $queries) {
            if (empty($queries)) {
                echo 'No queries found in ', $id, "\n";
                continue;
            }

            $db->beginTransaction();
            try {
                echo sprintf("Installing schema %s, %d queries found\n", $id, count($queries));
                foreach ($queries as $i => $query) {
                    echo sprintf("Running query #%d:\n  %s\n", $i, $query);
                    $db->query($query);
                    echo "\n\n";
                }
                $utime = microtime(true) - time();
                $now = date('Y-m-d H:i:s') . sprintf('.%03d', $utime * 1000);
                $db->insert($tableName, array(
                    'schema_id'    => $id,
                    'installed_at' => $now,
                ));
                $db->commit();
            } catch (Exception $e) {
                $db->rollBack();
                throw $e;
            }
        }
    }

    public function showInstallAction($module)
    {
        $install = $this->_prepareInstall($module);

        if (empty($install)) {
            echo 'No schemas to install.', "\n";
            return;
        }

        foreach ($install as $id => $queries) {
            if (empty($queries)) {
                echo '-- Schema: ', $id, ': No queries found', "\n";
                continue;
            }
            echo '-- Schema: ', $id, ":\n";
            foreach ($queries as $query) {
                echo "\n", $query, ";\n";
            }
        }
    }

    /**
     * @param string $module
     * @return array
     */
    protected function _prepareInstall($module)
    {
        $adapterClass = get_class($this->_getDbAdapter());
        $parts = explode('_', $adapterClass);

        $schemaType = self::getSchemaName(end($parts));

        $_schemas = $this->_getModuleSchemas($module);
        $schemas = @$_schemas[$schemaType];

        if (!$schemas) {
            return;
        }

        $db = $this->_getDbAdapter();
        $tablePrefix = $this->_db->getTablePrefix();
        $tableName = $tablePrefix . '_schemas';
        $hasTable = false;

        try {
            $info = $db->describeTable($tableName);
            $hasTable = !empty($info);
        } catch (Zend_Db_Statement_Exception $e) {
        }

        if (!$hasTable) {
            echo 'Schemas table not present, creating ... ';
            $db->query("CREATE TABLE {$db->quoteIdentifier($tableName)} (schema_id VARCHAR(191) PRIMARY KEY, installed_at VARCHAR(23))");
            echo 'done.', "\n";
        }

        $select = $db->select();
        $select->from($tableName);
        $select->where('schema_id IN (?)', array_keys($schemas));
        $installedSchemas = array_column(
            $select->query()->fetchAll(Zend_Db::FETCH_ASSOC),
            null,
            'schema_id'
        );

        foreach ($installedSchemas as $key => $row) {
            if (isset($schemas[$key])) {
                echo sprintf("%s already installed at %s\n", $key, $row['installed_at']);
                unset($schemas[$key]);
                continue;
            }
        }

        if (!$schemas) {
            echo 'No schemas to install.', "\n";
            return;
        }

        $queries = array();
        foreach ($schemas as $id => $file) {
            $queries[$id] = self::parseQueries(file_get_contents($file), $tablePrefix);
        }
        return $queries;
    }

    /**
     * @param $module
     * @return string[]
     */
    protected function _getModuleSchemas($module)
    {
        $moduleDir = APPLICATION_PATH . '/modules/' . $module;
        if (!is_dir($moduleDir)) {
            throw new RuntimeException("Module '$module' does not exist");
        }

        $schemaDir = $moduleDir . '/data/schema';

        if (!is_dir($schemaDir)) {
            return array();
        }

        $schemas = array(
        );
        foreach (scandir($schemaDir) as $entry) {
            if (substr($entry, -4) === '.sql' && is_file($schemaDir . '/' . $entry)) {
                $name = basename($entry, '.sql');
                $dot = strrpos($name, '.');
                if ($dot === false) {
                    echo 'Ignoring SQL file with unrecognized adapter: ', $module . '/' . $name, "\n";
                    continue;
                }
                $adapter = substr($name, $dot + 1);

                $schemaId = $module . '/' . $name;
                $schemas[$this->getSchemaName($adapter)][$schemaId] = $schemaDir . '/' . $entry;
            }
        }

        return $schemas;
    }

    /**
     * @return Zend_Db_Adapter_Abstract
     */
    protected function _getDbAdapter()
    {
        if (!$this->_db) {
            $application = $this->_getApplication()->bootstrap();

            /** @var Zefram_Db $db */
            $db = $application->getBootstrap()->getResource('Zefram_Db');

            if (!$db instanceof Zefram_Db) {
                throw new RuntimeException('Unable to bootstrap Zefram_Db resource');
            }

            $this->_db = $db;
        }

        return $this->_db->getAdapter();
    }

    /**
     * @param $sql
     * @param string $tablePrefix
     * @return string[]
     */
    public static function parseQueries($sql, $tablePrefix = '')
    {
        $sql = str_replace("\r\n", "\n", $sql);
        $sql = preg_replace('/\/\*.*?\*\//s', '', $sql);
        $sql = str_replace("\t", ' ', $sql);
        $sql = preg_replace('/[ ]+/', ' ', $sql);

        $sql = array_map(
            function ($line) {
                // Single line comments: MySQL '-- ', PostgreSQL '--'
                $line = preg_replace('/(--|#).*$/', '', $line);
                $line = trim($line);
                return $line;
            },
            explode("\n", $sql)
        );
        $sql = array_filter($sql, 'strlen');
        $sql = implode(' ', $sql);
        $sql = array_map('trim', explode(";", $sql));

        $sql = array_map(
            function ($query) use ($tablePrefix) {
                $query = preg_replace(
                    '/(CREATE TABLE)( IF NOT EXISTS)? (`|"|\\[)?/i',
                    '$1$2 $3' . $tablePrefix,
                    $query
                );
                $query = preg_replace(
                    '/(ALTER TABLE) (`|"|\\[)?/i',
                    '$1 $2' . $tablePrefix,
                    $query
                );
                $query = preg_replace(
                    '/(REFERENCES|CONSTRAINT) (`|"|\\[)?/i',
                    '$1 $2' . $tablePrefix,
                    $query
                );
                $query = preg_replace(
                    '/(CREATE( OR REPLACE)? VIEW) (`|"|\\[)?/i',
                    '$1 $2' . $tablePrefix,
                    $query
                );
                $query = preg_replace(
                    '/(CREATE INDEX) (`|"|\\[)?([^ ]+) (ON) (`|"|\\[)?/i',
                    '$1 $2' . $tablePrefix . '$3 $4 $5' . $tablePrefix,
                    $query
                );
                $query = preg_replace(
                    '/(UPDATE) (`|"|\\[)?([^ ]+) (SET)/i',
                    '$1 $2' . $tablePrefix . '$3 $4',
                    $query
                );
                $query = preg_replace(
                    '/ (FROM|JOIN|LEFT JOIN|RIGHT JOIN|OUTER JOIN|INNER JOIN|CROSS JOIN) (`|"|\\[)?([^ ]+)/i',
                    ' $1 $2' . $tablePrefix . '$3',
                    $query
                );
                $query = preg_replace(
                    '/(INSERT INTO) (`|"|\\[)?([^ ]+)/i',
                    '$1 $2' . $tablePrefix . '$3',
                    $query
                );
                return $query;
            },
            $sql
        );
        $sql = array_filter($sql, 'strlen');

        return $sql;
    }

    // Mapping between adapter
    public static function getSchemaName($name)
    {
        $name = strtolower($name);

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
