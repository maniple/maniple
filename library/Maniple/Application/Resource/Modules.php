<?php

/**
 * Existing module is not loaded / executed if the corresponding
 * resources.modules.moduleName settings is FALSE.
 *
 * Settings from resources.modules.moduleName.resourcesConfig override settings
 * from module's getResourcesConfig().
 *
 * Settings from resources.modules.moduleName.routesConfig override settings
 * from module's getRoutesConfig().
 *
 * @version 2014-05-20
 */
class Maniple_Application_Resource_Modules extends Zend_Application_Resource_ResourceAbstract
{
    /**
     * @var Zend_Loader_StandardAutoloader
     */
    protected $_autoloader;

    /**
     * @var array
     */
    protected $_modules;

    /**
     * @var ArrayObject
     */
    protected $_bootstraps;

    /**
     * Initialize modules
     *
     * @return ArrayObject
     */
    public function init() // {{{
    {
        $this->_initModules();
        $this->_initAutoloader();
        $this->_executeBootstraps();
        return $this->_bootstraps;
    } // }}}

    /**
     * Builds a list of available modules.
     *
     * @return void
     */
    protected function _initModules()
    {
        $bootstrap = $this->getBootstrap();
        $bootstrap->bootstrap('FrontController');

        $front = $bootstrap->getResource('FrontController');

        // Module detection (in Zend_Controller_Front::addModuleDirectory())
        // requires that each module contains controllers/ directory.
        $modules = array_map('dirname', $front->getControllerDirectory());

        $default = $front->getDefaultModule();

        $curBootstrapClass = get_class($bootstrap);

        $bootstraps = array();

        foreach ($modules as $module => $modulePath) {
            if (!is_dir($modulePath)) {
                throw new Exception(sprintf(
                    'Module directory "%s" does not exist', $modulePath
                ));
            }

            // Do not initialize module if module options are set to FALSE
            if (isset($options[$module]) && ($options[$module] === false)) {
                continue;
            }

            $modulePath = realpath($modulePath);
            $modulePrefix = $this->_formatModuleName($module);
            $bootstrapClass = $modulePrefix . '_Bootstrap';

            if (!class_exists($bootstrapClass, false)) {
                $bootstrapPath  = $modulePath . '/Bootstrap.php';
                if (file_exists($bootstrapPath)) {
                    $eMsgTpl = 'Bootstrap file found for module "%s" but bootstrap class "%s" not found';
                    include_once $bootstrapPath;
                    if (($default != $module)
                        && !class_exists($bootstrapClass, false)
                    ) {
                        throw new Zend_Application_Resource_Exception(sprintf(
                            $eMsgTpl, $module, $bootstrapClass
                        ));
                    } elseif ($default == $module) {
                        if (!class_exists($bootstrapClass, false)) {
                            $bootstrapClass = 'Bootstrap';
                            if (!class_exists($bootstrapClass, false)) {
                                throw new Zend_Application_Resource_Exception(sprintf(
                                    $eMsgTpl, $module, $bootstrapClass
                                ));
                            }
                        }
                    }
                } else {
                    continue;
                }
            }

            if ($bootstrapClass === $curBootstrapClass) {
                // If the found bootstrap class matches the one calling this
                // resource, don't re-execute.
                continue;
            }

            $bootstraps[$module] = array(
                'prefix' => $modulePrefix,
                'path' => $modulePath,
                'bootstrapClass' => $bootstrapClass,
                'stackIndex' => isset($options[$module['stackIndex']]) ?
                    (int) $options[$module['stackIndex']]
                    : 0,
            );
        }

        // TODO sort modules by stackIndex

        $this->_modules = $bootstraps;
    }

    /**
     * Initializes autoloader for classes withing modules.
     *
     * This autoloader maps Module_ClassName to module/library/ClassName.php
     *
     * @return void
     */
    protected function _initAutoloader() // {{{
    {
        if ($this->_autoloader === null) {
            // The default Zend_Application autoloader (Zend_Loader_Autoloader)
            // is initialized in the Zend_Application constructor.
            // This autoloader is a simple one, which only mapps _ and \\
            // to directory separators and utilizes include paths.
            // To load classes within modules we need something more sophisticated.
            $this->_autoloader = new Zend_Loader_StandardAutoloader();
            $this->_autoloader->register(); // add to autoload stack
        }

        // add libary dir to include path, see:
        // http://stackoverflow.com/questions/13377983/zend-framework-module-library
        foreach ($this->_modules as $module => $moduleInfo) {
            $path = $moduleInfo['path'] . '/library';

            if (is_dir($path)) {
                $this->_autoloader->registerPrefix($moduleInfo['prefix'] . '_', $path);
                // set_include_path($path . PATH_SEPARATOR . get_include_path());
            }
        }
    } // }}}

    /**
     * @param  array $bootstraps
     * @return ArrayObject
     */
    protected function _executeBootstraps()
    {
        $options = $this->getOptions();
        $bootstrap = $this->getBootstrap();

        $router = $bootstrap->getResource('frontController')->getRouter();

        $result = new ArrayObject(array(), ArrayObject::ARRAY_AS_PROPS);

        foreach ($this->_modules as $module => $moduleInfo) {
            if (isset($options[$module])) {
                $moduleOptions = $options[$module];
            } else {
                $moduleOptions = null;
            }

            $moduleBootstrap = new $moduleInfo['bootstrapClass']($bootstrap);

            // bootstrap any built-in / plugin resources, they will be stored in
            // the common resource container
            $moduleBootstrap->bootstrap();

            // get resources defined via getResourcesConfig() method
            // (to be lazy-loaded or arbitrarily named)
            if (method_exists($moduleBootstrap, 'getResourcesConfig')) {
                $resourcesConfig = $moduleBootstrap->getResourcesConfig($bootstrap);
            } else {
                $resourcesConfig = array();
            }

            // legacy setup
            foreach (array('getResources', 'onResources', 'onServices') as $legacy) {
                if (method_exists($moduleBootstrap, $legacy)) {
                    $resourcesConfig = $this->mergeOptions($resourcesConfig, $moduleBootstrap->$legacy($bootstrap));
                }
            }

            // echo '<div style="border:1px solid">', $module, '<pre>OPTIONS BEFORE:', print_r($resourcesConfig, 1);
            // echo 'Override with: ', print_r(@$moduleOptions['resourcesConfig'], 1), '<br/>';

            // override existing options with settings from application config
            if (isset($moduleOptions['resourcesConfig'])) {
                $resourcesConfig = $this->mergeOptions(
                    $resourcesConfig,
                    array_intersect_key($moduleOptions['resourcesConfig'], $resourcesConfig)
                );
            }

            foreach ($resourcesConfig as $name => $resource) {
                $bootstrap->setResource($name, $resource);
            }

            // echo '<br/><br/>OPTIONS AFTER:',print_r($resourcesConfig, 1), '</pre></div>';

            // get routes defined by getRoutesConfig()
            if (method_exists($moduleBootstrap, 'getRoutesConfig')) {
                $routesConfig = $moduleBootstrap->getRoutesConfig();
            } else {
                $routesConfig = array();
            }
            foreach (array('getRoutes', 'onRoutes') as $legacy) {
                if (method_exists($moduleBootstrap, $legacy)) {
                    $routesConfig = $this->mergeOptions($routesConfig, $moduleBootstrap->$legacy());
                }
            }
            // override existing options with settings from application config
            if (isset($options[$module]['routesConfig'])) {
                $routesConfig = $this->mergeOptions(
                    $routesConfig,
                    array_intersect_key($options[$module]['routesConfig'], $routesConfig)
                );
            }
            if (is_array($routesConfig)) {
                $routesConfig = new Zend_Config($routesConfig);
            }
            if (!$routesConfig instanceof Zend_Config) {
                throw new InvalidArgumentException('Route config must be an instance of Zend_Config');
            }
            $router->addConfig($routesConfig);

            $result[$module] = $moduleBootstrap;
        }

        // Ok, all modules loaded successfully, add search paths
        $this->_setupViewPaths();

        $this->_bootstraps = $result;
    }

    protected function _setupViewPaths()
    {
        $view = $this->_getBootstrapResource('View');

        if ($view instanceof Zend_View_Abstract) {
            foreach ($this->_modules as $module => $moduleInfo) {
                // TODO plugins?
                if (is_dir($moduleInfo['path'] . '/views/helpers')) {
                    $view->addHelperPath(
                        $moduleInfo['path'] . '/views/helpers/',
                        $moduleInfo['prefix'] . '_View_Helper_'
                    );
                }
            }
        }
    }

    /**
     * Get a resource from bootstrap, initialize it if necessary.
     *
     * @param  string $name
     * @return mixed
     */
    protected function _getBootstrapResource($name) // {{{
    {
        $bootstrap = $this->getBootstrap();

        if ($bootstrap->hasResource($name)) {
            $resource = $bootstrap->getResource($name);
        } elseif ($bootstrap->hasPluginResource($name) || method_exists($bootstrap, '_init' . $name)) {
            $bootstrap->bootstrap($name);
            $resource = $bootstrap->getResource($name);
        } else {
            $resource = null;
        }

        return $resource;
    } // }}}

    /**
     * Format a module name to the module class prefix
     *
     * @param  string $name
     * @return string
     */
    protected function _formatModuleName($name)
    {
        $name = strtolower($name);
        $name = str_replace(array('-', '.'), ' ', $name);
        $name = ucwords($name);
        $name = str_replace(' ', '', $name);
        return $name;
    }

    /**
     * Get bootstraps that have been run.
     *
     * @return ArrayObject
     */
    public function getExecutedBootstraps() // {{{
    {
        return $this->_bootstraps;
    } // }}}
}
