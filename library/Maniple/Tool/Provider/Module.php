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

        $dir = APPLICATION_PATH . '/modules/' . $moduleName;
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

        $this->createTests($modulePrefix, $moduleName, $moduleDir);

        @mkdir($moduleDir . '/public');

        @mkdir($moduleDir . '/controllers');

        @mkdir($moduleDir . '/languages');

        $this->_copyTemplateFile('composer.json', $moduleDir, $moduleName, $modulePrefix);
        $this->_copyTemplateFile('bower.json', $moduleDir, $moduleName, $modulePrefix);
        $this->_copyTemplateFile('.editorconfig', $moduleDir, $moduleName, $modulePrefix);
        $this->_copyTemplateFile('.gitignore', $moduleDir, $moduleName, $modulePrefix);

        $this->_registry->getResponse()->appendContent(
            sprintf('Created module %s in %s', $moduleName, $moduleDir)
        );
    }

    protected function _copyTemplateFile($fileName, $moduleDir, $moduleName, $modulePrefix, array $vars = array())
    {
        $targetFile = $moduleDir . '/' . $fileName;
        if (!file_exists($targetFile) || !filesize($targetFile)) {
            $fileContents = strtr(
                file_get_contents(__DIR__ . '/Module/template/' . $fileName),
                array_merge(
                    $vars,
                    array(
                        '%moduleName%'   => $moduleName,
                        '%modulePrefix%' => $modulePrefix,
                    )
                )
            );

            file_put_contents($targetFile, $fileContents);
            echo "Created " . $fileName . " file\n";
        }
    }

    protected function _createModuleBootstrap($moduleDir, $modulePrefix, $moduleName)
    {
        $this->_copyTemplateFile('Bootstrap.php', $moduleDir, $moduleName, $modulePrefix, array(
            'class __modulePrefix' => "class {$modulePrefix}",
        ));
    }

    protected function createTests($modulePrefix, $moduleName, $dir)
    {
        $this->_copyTemplateFile('phpunit.xml', $dir, $moduleName, $modulePrefix);

        @mkdir($dir . '/tests/' . $modulePrefix, 0777, true);
    }

    public function test($moduleName)
    {
        $moduleDir = APPLICATION_PATH . '/modules/' . $moduleName;
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
