<?php

class Maniple_Cli_Action_CreateModuleAction extends Maniple_Cli_Action_Abstract
{
    protected function _run($moduleName = null)
    {
        $moduleName = trim($moduleName);
        if (!strlen($moduleName)) {
            throw new Maniple_Cli_Action_Exception($this, 'Module name must not be empty');
        }
        if (!preg_match('/^[a-z][-a-z0-9]*$/', $moduleName)) {
            throw new Maniple_Cli_Action_Exception($this, 'Invalid module name: ' . $moduleName);
        }

        $dir = 'application/modules/' . $moduleName;
        if (!file_exists($dir) && !@mkdir($dir, 0777, true)) {
            throw new Maniple_Cli_Action_Exception($this, 'Unable to create module directory');
        }

        $moduleDir = realpath($dir);

        // generate module bootstrap class
        $modulePrefix = str_replace(' ', '', ucfirst(ucwords(str_replace('-', ' ', $moduleName))));

        if (!file_exists($moduleDir . '/Bootstrap.php')) {

            $bootstrapImpl = "<?php

class {$modulePrefix}_Bootstrap extends Maniple_Application_Module_Bootstrap
{
    public function getResourceConfig()
    {
        return require dirname(__FILE__) . '/configs/resource.config.php';
    }

    public function getRouteConfig()
    {
        return require dirname(__FILE__) . '/configs/route.config.php';
    }
}
";

            file_put_contents($moduleDir . '/Bootstrap.php', $bootstrapImpl);
        }

        // generate empty config files
        @mkdir($moduleDir . '/configs');

        if (!file_exists($moduleDir .'/configs/resource.config.php')) {
            file_put_contents($moduleDir .'/configs/resource.config.php', "<?php return array(\n    // Module resources config\n);");
        }
        if (!file_exists($moduleDir .'/configs/route.config.php')) {
            file_put_contents($moduleDir .'/configs/route.config.php', "<?php return array(\n    // Module routes config\n);");
        }

        // generate library/ here all autoload classes will be stored
        @mkdir($moduleDir . '/library');

        @mkdir($moduleDir . '/views/layouts', 0777, true);
        @mkdir($moduleDir . '/views/scripts', 0777, true);

        $this->createTests($modulePrefix, $moduleDir);

        @mkdir($moduleDir . '/public');

        @mkdir($moduleDir . '/controllers');

        echo "[  OK  ] Created module ", $moduleName, " in ", $moduleDir, "\n";
    }

    protected function createTests($moduleName, $dir)
    {
        $xml = <<<END
<?xml version="1.0" encoding="UTF-8"?>
<phpunit backupGlobals="false"
         bootstrap="tests/bootstrap.php"
         colors="true">
    <testsuites>
        <testsuite name="{$moduleName} Test Suite">
            <directory suffix=".php">./tests</directory>
        </testsuite>
    </testsuites>
    <filter>
        <whitelist>
            <directory suffix=".php">./controllers</directory>
            <directory suffix=".php">./library</directory>
        </whitelist>
    </filter>
</phpunit>
END;

        @mkdir($dir . '/tests');

        $configPath = $dir . '/phpunit.xml.dist';
        if (!file_exists($configPath)) {
            file_put_contents($configPath, $xml);
        }
    }
}