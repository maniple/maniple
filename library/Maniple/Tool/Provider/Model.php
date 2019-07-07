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

        $moduleDir = 'application/modules/' . $moduleName;
        if (!is_dir($moduleDir)) {
            throw new Exception("Module '$moduleName' does not exist");
        }

        $modulePrefix = str_replace(' ', '', ucfirst(ucwords(str_replace('-', ' ', $moduleName))));
        $modelDir = $moduleDir . '/library/' . $modulePrefix . '/Model';

        @mkdir($modelDir . '/Table', 0777, true);
        @mkdir($modelDir . '/Rowset', 0777, true);

        $modelNamePluralized = $modelName . 's'; // TODO Use inflector?

        $filter = new Zend_Filter_Word_CamelCaseToUnderscore();
        $tableName = strtolower($filter->filter($modelNamePluralized));
        $idColumnName = strtolower($filter->filter($modelName)) . '_id';

        $rowClass = $modulePrefix . '_Model_' . $modelName;
        $tableClass = $modulePrefix . '_Model_Table_' . $modelNamePluralized;
        $rowsetClass = $modulePrefix . '_Model_Rowset_' . $modelNamePluralized;

        $rowClassFile = $modelDir . '/' . $modelName . '.php';
        $tableClassFile = $modelDir . '/Table/' . $modelNamePluralized . '.php';
        $rowsetClassFile = $modelDir . '/Rowset/' . $modelNamePluralized . '.php';

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
}
");

        if (file_exists($tableClassFile)) {
            throw new Exception("Model table class file already exists: {$tableClassFile}");
        }

        file_put_contents($tableClassFile,
"<?php

/**
 * @method {$rowClass} findRow(mixed \$id)
 * @method {$rowClass} createRow(array \$data = array(), string \$defaultSource = null)
 * @method {$rowsetClass} find(mixed \$key, mixed ...\$keys)
 * @method {$rowsetClass} fetchAll(string|array|Zend_Db_Table_Select \$where = null, string|array \$order = null, int \$count = null, int \$offset = null)
 */
class {$tableClass} extends Zefram_Db_Table
{
    const className = __CLASS__;

    protected \$_rowClass = {$rowClass}::className;

    protected \$_rowsetClass = {$rowsetClass}::className;

    protected \$_name = '{$tableName}';

    protected \$_referenceMap = array();
}
");

        if (file_exists($rowsetClassFile)) {
            throw new Exception("Model rowset class file already exists: {$rowsetClassFile}");
        }

        file_put_contents($rowsetClassFile,
"<?php

/**
 * @method bool setTable({$tableClass} \$table)
 * @method {$tableClass} getTable()
 * @method {$rowClass}|null current()
 * @method {$rowClass} offsetGet(string \$offset)
 * @method {$rowClass} getRow(int \$position, \$seek = false)
 */
class {$rowsetClass} extends Zend_Db_Table_Rowset
{
    const className = __CLASS__;

    protected \$_tableClass = {$tableClass}::className;

    protected \$_rowClass = {$rowClass}::className;
}
");

        $paddedIdColumnName = sprintf('%-15s', $idColumnName);

        @mkdir($moduleDir . '/data/schema', 0777, true);
        file_put_contents($moduleDir . '/data/schema/' . $modulePrefix . '.MYSQL.sql',
"-- {$modulePrefix} schema for MySQL

CREATE TABLE /* PREFIX */{$tableName} (

    {$paddedIdColumnName} INT UNSIGNED PRIMARY KEY AUTO_INCREMENT

) ENGINE=InnoDB CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
");
        file_put_contents($moduleDir . '/data/schema/' . $modulePrefix . '.PGSQL.sql',
"-- {$modulePrefix} schema for PostgreSQL

CREATE TABLE /* PREFIX */{$tableName} (

    {$paddedIdColumnName} SERIAL PRIMARY KEY

);
");
        file_put_contents($moduleDir . '/data/schema/' . $modulePrefix . '.SQLITE.sql',
            "-- {$modulePrefix} schema for SQLite

CREATE TABLE /* PREFIX */{$tableName} (

    {$paddedIdColumnName} INTEGER PRIMARY KEY

);
");
    }
}
