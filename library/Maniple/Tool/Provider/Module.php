<?php

class Maniple_Tool_Provider_Module extends Zend_Tool_Framework_Provider_Abstract
{
    public function create($moduleName)
    {
        $moduleName = trim($moduleName);
        if (!strlen($moduleName)) {
            throw new Zend_Tool_Framework_Client_Exception('Module name must not be empty');
        }
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
            file_put_contents($moduleDir .'/configs/routes.config.php', "<?php return array(\n    // Module routes config\n);");
        }
        if (!file_exists($moduleDir .'/configs/resource.config.php')) {
            file_put_contents($moduleDir .'/configs/resource.config.php', "<?php return array(\n    // Module resources config\n);");
        }

        // generate library/ here all autoload classes will be stored
        @mkdir($moduleDir . '/library');

        @mkdir($moduleDir . '/views/layouts', 0777, true);
        @mkdir($moduleDir . '/views/scripts', 0777, true);

        $this->createTests($modulePrefix, $moduleDir);

        @mkdir($moduleDir . '/public');

        @mkdir($moduleDir . '/controllers');

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
    public function getResourceConfig()
    {
        return require dirname(__FILE__) . '/configs/resource.config.php';
    }

    /**
     * Register autoloader paths
     */
    protected function _initAutoloader()
    {
        Zend_Loader_AutoloaderFactory::factory(array(
            'Zend_Loader_StandardAutoloader' => array(
                'prefixes' => array(
                    '{$modulePrefix}_' => dirname(__FILE__) . '/library/',
                ),
            ),
        ));
    }

    /**
     * Register module routes
     */
    protected function _initRouter()
    {
        /** @var Zend_Application_Bootstrap_BootstrapAbstract \$bootstrap */
        \$bootstrap = \$this->getApplication();
        \$bootstrap->bootstrap('FrontController');

        /** @var Zend_Controller_Router_Rewrite \$router */
        \$router = \$bootstrap->getResource('FrontController')->getRouter();
        \$router->addConfig(new Zend_Config(require dirname(__FILE__) . '/configs/routes.config.php'));
    }

    /**
     * Register view helper paths
     */
    protected function _initView()
    {
        /** @var Zend_Application_Bootstrap_BootstrapAbstract \$bootstrap */
        \$bootstrap = \$this->getApplication();
        \$bootstrap->bootstrap('View');

        /** @var Zend_View_Abstract \$view */
        \$view = \$bootstrap->getResource('View');
        \$view->addHelperPath(dirname(__FILE__) . '/library/View/Helper/', '{$modulePrefix}_View_Helper_');
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
        $configPath = $dir . '/phpunit.xml.dist';
        if (!file_exists($configPath)) {
            file_put_contents($configPath, $xml);
        }

        @mkdir($dir . '/tests');

        // prepare tests bootstrap file
        $testsBootstrap = $dir . '/tests/bootstrap.php';
        if (!file_exists($testsBootstrap)) {
            file_put_contents($testsBootstrap, <<<END
<?php

error_reporting(E_ALL | E_STRICT);
ini_set('display_startup_errors', 1);
ini_set('display_errors', 1);

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
    die('Unable to find autoload.php');
}

require_once \$autoload;

END
            );
        }
    }
}