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
        $adapterClass = get_class($this->_getDbAdapter());
        $parts = explode('_', $adapterClass);

        $schemaType = self::getSchemaName(end($parts));

        $_schemas = $this->_getModuleSchemas($module);
        $schemas = @$_schemas[$schemaType];

        if (!$schemas) {
            echo 'No schemas to install.', "\n";
            return;
        }

        $db = $this->_getDbAdapter();
        $tablePrefix = $this->_db->getTablePrefix();
        $tableName = $tablePrefix . '_schemas';
        $hasTable = false;
        try {
            $db->describeTable($tableName);
            $hasTable = true;
        } catch (Zend_Db_Statement_Exception $e) {

        }
        if (!$hasTable) {
            echo 'Schemas table not present, creating ... ';
            $db->query("CREATE TABLE {$db->quoteIdentifier($tableName)} (schema_id VARCHAR(255) PRIMARY KEY, installed_at INTEGER NOT NULL)");
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
                echo sprintf("%s already installed at %s\n", $key, date('Y-m-d H:i:s', $row['installed_at']));
                unset($schemas[$key]);
                continue;
            }
        }

        if (!$schemas) {
            echo 'No schemas to install.', "\n";
            return;
        }

        foreach ($schemas as $id => $file) {
            $queries = self::parseQueries(file_get_contents($file), $tablePrefix);
            if (!$queries) {
                echo 'No queries found in ', $file, "\n";
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
                $db->insert($tableName, array(
                    'schema_id' => $id,
                    'installed_at' => time(),
                ));
                $db->commit();
            } catch (Exception $e) {
                $db->rollBack();
                throw $e;
            }
        }
    }

    /**
     * @param $module
     * @return string[]
     */
    protected function _getModuleSchemas($module)
    {
        $moduleDir = 'application/modules/' . $module;
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
                    '$1 $2' . $tablePrefix . ' $3 $4' . $tablePrefix,
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
