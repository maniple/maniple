<?php

class Maniple_Tool_Provider_Model extends Zend_Tool_Framework_Provider_Abstract
{
    const className = __CLASS__;

    /**
     * Create module
     *
     * @param string $name     The name of the model to be created
     * @param string $module   The module in which the controller should be created
     * @throws Zend_Tool_Framework_Client_Exception
     */
    public function create($name, $module)
    {
        // Zend_Tool cannot handle parameters which start with the same first letter,
        // it throws Zend_Console_Getopt_Exception with message saying that option
        // is being defined more than once.
        // Unfortunately ZF Tool documentation does not mention that.
        // So modelName and moduleName cannot be used as provider's action params.
        $modelName = $name;
        $moduleName = $module;

        if (!preg_match('/^[A-Z][a-zA-Z0-9]*$/', $modelName)) {
            throw new Exception("Invalid model name: '$modelName'. Name must follow upper camel-case convention.");
        }

        $moduleDir = APPLICATION_PATH . '/modules/' . $moduleName;
        if (!is_dir($moduleDir)) {
            throw new Exception("Module '$moduleName' does not exist");
        }

        $modulePrefix = str_replace(' ', '', ucfirst(ucwords(str_replace('-', ' ', $moduleName))));
        $modelDir = $moduleDir . '/library/' . $modulePrefix . '/Model';

        @mkdir($modelDir . '/DbTable', 0777, true);

        $modelNamePluralized = $modelName . 's'; // TODO Use inflector?

        $filter = new Zend_Filter_Word_CamelCaseToUnderscore();
        $tableName = strtolower($filter->filter($modelNamePluralized));
        $idColumnName = strtolower($filter->filter($modelName)) . '_id';

        $rowClass = $modulePrefix . '_Model_' . $modelName;
        $tableClass = $modulePrefix . '_Model_DbTable_' . $modelNamePluralized;

        $rowClassFile = $modelDir . '/' . $modelName . '.php';
        $tableClassFile = $modelDir . '/DbTable/' . $modelNamePluralized . '.php';

        if (file_exists($rowClassFile)) {
            throw new Exception("Model file already exists: {$rowClassFile}");
        }

        file_put_contents($rowClassFile,
"<?php

/**
 * @method {$tableClass} getTable()
 */
class {$rowClass} extends Zefram_Db_Table_Row
{
    const className = __CLASS__;

    protected \$_tableClass = {$tableClass}::className;

    /**
     * @return int
     */
    public function getId()
    {
        /** @noinspection PhpUndefinedFieldInspection */
        return (int) \$this->{$idColumnName};
    }
}
");

        if (file_exists($tableClassFile)) {
            throw new Exception("Model table class file already exists: {$tableClassFile}");
        }

        // Define return type of find() and fetchAll() as combined Rowset/array
        // https://stackoverflow.com/questions/10706835/phpstorm-correct-phpdoc-for-a-collection-of-objects
        file_put_contents($tableClassFile,
"<?php

/**
 * @method {$rowClass} createRow(array \$data = array(), string \$defaultSource = null)
 * @method {$rowClass}|null fetchRow(string|array|Zend_Db_Table_Select \$where = null, string|array \$order = null, int \$offset = null)
 * @method {$rowClass}|null findRow(mixed \$id)
 * @method Zend_Db_Table_Rowset_Abstract|{$rowClass}[] find(mixed \$key, mixed ...\$keys)
 * @method Zend_Db_Table_Rowset_Abstract|{$rowClass}[] fetchAll(string|array|Zend_Db_Table_Select \$where = null, string|array \$order = null, int \$count = null, int \$offset = null)
 */
class {$tableClass} extends Zefram_Db_Table
{
    const className = __CLASS__;

    protected \$_rowClass = {$rowClass}::className;

    protected \$_name = '{$tableName}';

    protected \$_referenceMap = array();
}
");

        $paddedIdColumnName = sprintf('%-15s', $idColumnName);

        @mkdir($moduleDir . '/data/schema', 0777, true);
        file_put_contents($moduleDir . '/data/schema/' . $tableName . '.mysql.sql',
"-- {$modulePrefix} schema for MySQL

CREATE TABLE {$tableName} (

    {$paddedIdColumnName} INT PRIMARY KEY AUTO_INCREMENT

) ENGINE=InnoDB CHARACTER SET utf8mb4;
");
        file_put_contents($moduleDir . '/data/schema/' . $tableName . '.pgsql.sql',
"-- {$modulePrefix} schema for PostgreSQL

CREATE TABLE {$tableName} (

    {$paddedIdColumnName} SERIAL PRIMARY KEY

);
");
        file_put_contents($moduleDir . '/data/schema/' . $tableName . '.sqlite.sql',
            "-- {$modulePrefix} schema for SQLite

CREATE TABLE {$tableName} (

    {$paddedIdColumnName} INTEGER PRIMARY KEY

);
");

        $schemaFile = $moduleDir . '/data/schema/' . $moduleName . '.schema.php';
        if (file_exists($schemaFile)) {
            $schema = (array) require $schemaFile;
        } else {
            $schema = array();
        }
        $schema[$tableName] = array(
            'columns' => array(
                $idColumnName => array(
                    'primary' => true,
                    'autoincrement' => true,
                ),
            ),
        );
        // file_put_contents($schemaFile, "<?php\n\nreturn " . Maniple_Filter_VarExport::filterStatic($schema) . ";\n");
    }
}
