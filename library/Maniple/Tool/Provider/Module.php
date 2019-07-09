<?php

class Maniple_Tool_Provider_Module extends Zend_Tool_Framework_Provider_Abstract
{
    const className = __CLASS__;

    /**
     * Create module
     *
     * @param string $moduleName
     * @throws Zend_Tool_Framework_Client_Exception
     */
    public function create($moduleName)
    {
        $moduleName = trim($moduleName);
        if (!strlen($moduleName)) {
            throw new Zend_Tool_Framework_Client_Exception('Module name must not be empty');
        }

        // Convert camel-case to dash-case
        $filter = new Zend_Filter_Word_CamelCaseToDash();
        $moduleName = strtolower($filter->filter($moduleName));

        if (!preg_match('/^[a-z][-a-z0-9]*$/', $moduleName)) {
            throw new Zend_Tool_Framework_Client_Exception('Invalid module name: ' . $moduleName);
        }

        $dir = 'application/modules/' . $moduleName;
        if (!file_exists($dir) && !@mkdir($dir, 0777, true)) {
            throw new Zend_Tool_Framework_Client_Exception('Unable to create module directory');
        }

        $moduleDir = realpath($dir);

        // generate module bootstrap class
        $modulePrefix = str_replace(' ', '', ucfirst(ucwords(str_replace('-', ' ', $moduleName))));

        $this->_createModuleBootstrap($moduleDir, $modulePrefix, $moduleName);

        // generate empty config files
        @mkdir($moduleDir . '/configs');

        if (!file_exists($moduleDir .'/configs/routes.config.php')) {
            file_put_contents($moduleDir .'/configs/routes.config.php', "<?php\n\nreturn array(\n    // Module routes config\n);\n");
        }
        if (!file_exists($moduleDir .'/configs/resources.config.php')) {
            file_put_contents($moduleDir .'/configs/resources.config.php', "<?php\n\nreturn array(\n    // Module resources config\n);\n");
        }

        // generate library/ here all autoload classes will be stored
        @mkdir($moduleDir . '/library/' . $modulePrefix, 0777, true);

        @mkdir($moduleDir . '/views/scripts/' . $moduleName, 0777, true);

        $this->createTests($modulePrefix, $moduleDir);

        @mkdir($moduleDir . '/public');

        @mkdir($moduleDir . '/controllers');

        @mkdir($moduleDir . '/languages');

        $this->_registry->getResponse()->appendContent(
            sprintf('Created module %s in %s', $moduleName, $moduleDir)
        );
    }

    protected function _createModuleBootstrap($moduleDir, $modulePrefix, $moduleName)
    {
        if (!file_exists($moduleDir . '/Bootstrap.php')) {
            $bootstrapImpl = "<?php

class {$modulePrefix}_Bootstrap extends Maniple_Application_Module_Bootstrap
{
    public function getModuleDependencies()
    {
        return array();
    }

    public function getResourcesConfig()
    {
        return require dirname(__FILE__) . '/configs/resources.config.php';
    }

    public function getRoutesConfig()
    {
        return require dirname(__FILE__) . '/configs/routes.config.php';
    }

    public function getTranslationsConfig()
    {
        return array(
            'scan'    => Zend_Translate::LOCALE_DIRECTORY,
            'content' => dirname(__FILE__) . '/languages',
        );
    }

    public function getViewConfig()
    {
        return array(
            'scriptPaths' => dirname(__FILE__) . '/views',
            'helperPaths' => array(
                '{$modulePrefix}_View_Helper_' => dirname(__FILE__) . '/library/{$modulePrefix}/View/Helper/',
            ),
        );
    }

    /**
     * Register autoloader paths
     */
    protected function _initAutoloader()
    {
        Zend_Loader_AutoloaderFactory::factory(array(
            'Zend_Loader_StandardAutoloader' => array(
                'prefixes' => array(
                    '{$modulePrefix}_' => dirname(__FILE__) . '/library/{$modulePrefix}/',
                ),
            ),
        ));
    }

    /**
     * Setup view path spec
     */
    protected function _initViewRenderer()
    {
        /** @var Zefram_Controller_Action_Helper_ViewRenderer \$viewRenderer */
        \$viewRenderer = Zend_Controller_Action_HelperBroker::getStaticHelper('ViewRenderer');
        \$viewRenderer->setViewScriptPathSpec(':module/:controller/:action.:suffix', '{$moduleName}');
        \$viewRenderer->setViewSuffix('twig', '{$moduleName}');
    }
}
";

            file_put_contents($moduleDir . '/Bootstrap.php', $bootstrapImpl);
        }
    }

    protected function createTests($moduleName, $dir)
    {
        $xml = <<<END
<phpunit bootstrap="tests/bootstrap.php" colors="true">
    <php>
        <ini name="display_errors" value="On" />
        <ini name="display_startup_errors" value="On" />
        <ini name="error_reporting" value="-1" />
    </php>
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
        $configPath = $dir . '/phpunit.xml';
        if (!file_exists($configPath)) {
            file_put_contents($configPath, $xml);
        }

        @mkdir($dir . '/tests');

        // prepare tests bootstrap file
        $testsBootstrap = $dir . '/tests/bootstrap.php';
        if (!file_exists($testsBootstrap)) {
            file_put_contents($testsBootstrap, "<?php

// find autoload.php moving upwards, so that tests can be executed
// even if the library itself lies in the vendor/ directory of another
// project

\$dir = dirname(__FILE__);
\$autoload = null;

while (\$parent = \$dir . '/..') {
    if (file_exists(\$path = \$parent . '/vendor/autoload.php')) {
        \$autoload = \$path;
        break;
    }
    \$dir = \$parent;
}
if (empty(\$autoload)) {
    die('Unable to find vendor/autoload.php file');
}

/** @noinspection PhpIncludeInspection */
require_once \$autoload;

Zend_Loader_AutoloaderFactory::factory(array(
    'Zend_Loader_StandardAutoloader' => array(
        'prefixes' => array(
            '{$moduleName}_' => dirname(dirname(__FILE__)) . '/library/{$moduleName}/',
        ),
    ),
));
"
            );
        }
    }

    public function test($moduleName)
    {
        $moduleDir = 'application/modules/' . $moduleName;
        if (!file_exists($moduleDir)) {
            throw new Zend_Tool_Framework_Client_Exception('Unable to find module ' . $moduleName);
        }

        $autoload = null;
        $dir = $moduleDir;
        while ($parent = $dir . '/..') {
            if (file_exists($path = $parent . '/vendor/autoload.php')) {
                $autoload = realpath($path);
                break;
            }
            $dir = $parent;
        }

        $phpunit = null;
        if ($autoload) {
            $phpunit = dirname($autoload) . '/bin/phpunit';

            if (!file_exists($phpunit)) {
                $phpunit = null;
            }
        }

        if (!$phpunit) {
            throw new Zend_Tool_Framework_Client_Exception('Unable to find phpunit binary');
        }

        $cwd = getcwd();
        chdir($moduleDir);
        passthru($phpunit, $error);
        chdir($cwd);

        exit($error);
    }

    public function setup($moduleName)
    {
        Maniple_Tool_Provider_Module_Setup::run($moduleName);
    }

    public function install($moduleSpec)
    {
        Maniple_Tool_Provider_Module_Install::run($moduleSpec);
    }
}
