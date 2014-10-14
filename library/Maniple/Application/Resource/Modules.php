<?php

/**
 * Existing module is not loaded / executed if the corresponding
 * resources.modules.moduleName settings is FALSE.
 *
 * Modules can depend on other modules via calls to bootstrapModule().
 *
 * Settings from resources.modules.moduleName.resourcesConfig override settings
 * from module's getResourcesConfig().
 *
 * Settings from resources.modules.moduleName.routesConfig override settings
 * from module's getRoutesConfig().
 *
 * @version 2014-07-13
 */
class Maniple_Application_Resource_Modules
    extends Maniple_Application_Resource_ResourceAbstract
    implements Maniple_Application_ModuleBootstrapper
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

    public function __construct($options = null)
    {
        parent::__construct($options);
        $this->_initModules();
        $this->_initAutoloader();        
    }

    /**
     * Initialize modules
     *
     * @return ArrayObject
     */
    public function init() // {{{
    {
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
            // modules should reside in the module/ subdirectory
            // if controllers directory exists, add it to front controller, as
            // otherwise it would not be found by the dispatcher
            if (file_exists($modulePath . '/module')) {
                $modulePath .= '/module';
                // add controllers directory without checking if it exists
                // - the way a module directory is retrieved from front controller
                // depends on whether module's controller directory is added to
                // dispatcher (not the module directory itself)
                $front->addControllerDirectory($modulePath . '/controllers', $module);
            }

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
        $this->_bootstraps = new ArrayObject(array(), ArrayObject::ARRAY_AS_PROPS);
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
     * @var array
     */
    protected $_whileBootstrapping;

    /**
     * @param  string $moduleName
     * @return Zend_Application_Module_Bootstrap
     * @throws Exception
     */
    public function bootstrapModule($moduleName)
    {
        if (empty($this->_modules[$moduleName])) {
            throw new InvalidArgumentException("Module {$moduleName} was not found");
        }

        if (isset($this->_bootstraps->{$moduleName})) {
            return $this->_bootstraps->{$moduleName};
        }

        if (isset($this->_whileBootstrapping[$moduleName])) {
            throw new Exception('Cyclic module dependency detected; module ' . $moduleName . ' is during bootstrap process');
        }

        $moduleInfo = $this->_modules[$moduleName];
        $this->_whileBootstrapping[$moduleName] = true;

        $bootstrap = $this->getBootstrap();

        $options = $this->getOptions();

        if (isset($options[$moduleName])) {
            $moduleOptions = $options[$moduleName];
        } else {
            $moduleOptions = null;
        }

        $moduleBootstrap = new $moduleInfo['bootstrapClass']($bootstrap);

        if ($moduleBootstrap instanceof Maniple_Application_Module_Bootstrap) {
            $moduleBootstrap->setModuleManager($this);
        }

        // get resources defined via getResourcesConfig() method
        // (to be lazy-loaded or arbitrarily named)
        if (method_exists($moduleBootstrap, 'getResourcesConfig')) {
            $resourcesConfig = $moduleBootstrap->getResourcesConfig();
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

        foreach ($resourcesConfig as $resourceName => $resource) {
            $bootstrap->setResource($resourceName, $resource);
        }

        // echo '<br/><br/>OPTIONS AFTER:',print_r($resourcesConfig, 1), '</pre></div>';

        // get routes defined by getRoutesConfig()
        if (method_exists($moduleBootstrap, 'getRoutesConfig')) {
            $routesConfig = (array) $moduleBootstrap->getRoutesConfig();
        } else {
            $routesConfig = array();
        }
        foreach (array('getRoutes', 'onRoutes') as $legacy) {
            if (method_exists($moduleBootstrap, $legacy)) {
                $routesConfig = $this->mergeOptions($routesConfig, $moduleBootstrap->$legacy());
            }
        }
        // override existing options with settings from application config
        if (isset($options[$moduleName]['routesConfig'])) {
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

        $router = $bootstrap->getResource('frontController')->getRouter();
        $router->addConfig($routesConfig);

        // bootstrap any built-in / plugin resources, they will be stored in
        // the common resource container
        $moduleBootstrap->bootstrap();

        // notify bootstrapping is complete
        if (method_exists($moduleBootstrap, 'onBootstrap')) {
            $moduleBootstrap->onBootstrap($this);
        }

        unset($this->_whileBootstrapping[$moduleName]);
        $this->_bootstraps->{$moduleName} = $moduleBootstrap;

        return $moduleBootstrap;
    }

    /**
     * @param  array $bootstraps
     * @return ArrayObject
     */
    protected function _executeBootstraps()
    {
        $bootstrap = $this->getBootstrap();

        foreach ($this->_modules as $module => $moduleInfo) {
            $this->bootstrapModule($module);
        }

        // Ok, all modules loaded successfully, add search paths
        $this->_setupViewPaths();

        // great, all modules are loaded, notify of module loading completion
        foreach ($this->_bootstraps as $moduleBootstrap) {
            if (method_exists($moduleBootstrap, 'onModulesLoaded')) {
                $moduleBootstrap->onModulesLoaded($this);
            }
        }
    }

    protected function _setupViewPaths()
    {
        $view = $this->_getBootstrapResource('View');

        $helperPaths = array(
            '/library/View/Helper',
            '/views/helpers',
        );

        if ($view instanceof Zend_View_Abstract) {
            foreach ($this->_modules as $module => $moduleInfo) {
                foreach ($helperPaths as $path) {
                    if (is_dir($moduleInfo['path'] . $path)) {
                        $view->addHelperPath(
                            $moduleInfo['path'] . $path,
                            $moduleInfo['prefix'] . '_View_Helper_'
                        );
                    }
                }
            }
        }
    }

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

    /**
     * @var Zend_Loader_PluginLoader_Interface
     */
    protected $_taskLoader;

    /**
     * @var Maniple_Application_Module_Task_TaskInterface[]
     */
    protected $_taskRegistry;

    /**
     * @return Zend_Loader_PluginLoader_Interface
     */
    public function getTaskLoader()
    {
        if (empty($this->_taskLoader)) {
            $prefixes = array(
                'Maniple_Application_Module_Task_' => 'Maniple/Application/Module/Task/',
            );
            $this->_taskLoader = new Zend_Loader_PluginLoader($prefixes);
        }
        return $this->_taskLoader;
    }

    /**
     * @param  string|Maniple_Application_Module_Task_TaskInterface $task
     * @param  Maniple_Application_Module_Bootstrap $module
     */
    public function runTask($task, Maniple_Application_Module_Bootstrap $module)
    {
        if (!$task instanceof Maniple_Application_Module_Task_TaskInterface) {
            $taskName = (string) $task;
            if (isset($this->_taskRegistry[$taskName])) {
                $task = $this->_taskRegistry[$taskName];
            } else {
                $taskClass = $this->getTaskLoader()->load($taskName);
                $task = new $taskClass();
                if (!$task instanceof Maniple_Application_Module_Task_TaskInterface) {
                    throw new InvalidArgumentException('Task must be an instance of Maniple_Application_Module_Task_TaskInterface, received ' . get_class($task));
                }
                $this->_taskRegistry[$taskName] = $task;
            }
        }
        return $task->run($module);
    }
}
