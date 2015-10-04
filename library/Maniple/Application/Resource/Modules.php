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
    const STATE_DEFAULT       = 0;
    const STATE_BOOTSTRAPPING = 1;
    const STATE_BOOTSTRAPPED  = 2;

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
        $this->preInit(); // this may be called externally
        $this->_executeBootstraps();
        return $this->_bootstraps;
    } // }}}

    protected $_configured = false;

    public function preInit()
    {
        if ($this->_configured) {
            return;
        }

        $this->_initModules();
        $this->_initAutoloader();

        // instantiates modules, gets configs
        foreach ($this->_modules as $module => $moduleInfo) {
            $config = $this->configModule($module);
        }

        $this->configResources();

        $this->_configured = true;
        return $this;
    }

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
                'state' => self::STATE_DEFAULT,
                'stackIndex' => isset($options['stackIndex']) ?
                    (int) $options['stackIndex']
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
     * Reads module configuration and merges it with global config
     *
     * @param  string $moduleName
     * @return Zend_Application_Module_Bootstrap
     * @throws Exception
     */
    public function configModule($moduleName)
    {
        if (empty($this->_modules[$moduleName])) {
            throw new InvalidArgumentException("Module {$moduleName} was not found");
        }

        if (isset($this->_bootstraps->{$moduleName})) {
            return $this->_bootstraps->{$moduleName};
        }

        $moduleInfo = $this->_modules[$moduleName];

        $bootstrap = $this->getBootstrap();

        $moduleBootstrap = new $moduleInfo['bootstrapClass']($bootstrap);

        if ($moduleBootstrap instanceof Maniple_Application_Module_Bootstrap) {
            $moduleBootstrap->setModuleManager($this);
        }

        $this->_bootstraps->{$moduleName} = $moduleBootstrap;

        return $moduleBootstrap;
    }

    public function configResources()
    {
        $bootstrap = $this->getBootstrap();
        $resources = array();

        foreach ($this->_bootstraps as $moduleName => $moduleBootstrap) {
            $moduleOptions = $this->getOption($moduleName);

            // get resources defined via getResourcesConfig() method
            // (to be lazy-loaded or arbitrarily named)
            if (method_exists($moduleBootstrap, 'getResourceConfig')) {
                $resourcesConfig = $moduleBootstrap->getResourceConfig();
            } elseif (method_exists($moduleBootstrap, 'getResourcesConfig')) {
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

            $resources = $this->mergeOptions($resources, $resourcesConfig);
        }

        // merge resources with application config
        // ideally modules resources should be added as the first resource
        // in application config, otherwise this will have no action
        // 'resources' key is hardcoded in Zend_Application_Bootstrap_BootstrapAbstract

        $this->_mergeResources($resources);
    }

    protected function _mergeResources(array $resources)
    {
        $bootstrap = $this->getBootstrap();

        // retrieve existing resources options from bootstrap and merge
        // them with module resource options
        // Nope, Zend_Application_Bootstrap_Bootstrapper has no getOptions() method.
        // but Zend_Application_Bootstrap_BootstrapAbstract has
        if (method_exists($bootstrap, 'getOptions')) {
            $options = $bootstrap->getOptions();
            $bootstrapResources = isset($options['resources']) ? (array) $options['resources'] : array();
        } else {
            $bootstrapResources = array();
        }

        // FrontController won't be modified this way, as it is already
        // bootstrapped, due to retrieval of paths

        // Don't re-register resources already existing in bootstrap. Register
        // only those, that has been changed.
        // Make sure that 'modules' resource is bootstrapped before all other
        // resources, so that updating the 'resources' config can be taken into
        // account during the bootstrapping process.
        foreach ($resources as $name => $options) {
            if (isset($bootstrapResources[$name]) && is_array($bootstrapResources[$name])) {
                // bootstrap options have greater priority than module options
                $mergedResource = $this->mergeOptions($options, $bootstrapResources[$name]);
                if ($mergedResource != $bootstrapResources[$name]) {
                    $resources[$name] = $mergedResource;
                } else {
                    unset($resources[$name]);
                }
            }
        }

        if ($resources) {
            // echo '<pre>';print_r($resources);exit;
            $bootstrap->setOptions(array('resources' => $resources));
        }
    }

    /**
     * @param string $name
     * @return mixed
     */
    public function getOption($name)
    {
        if (isset($this->_options[$name])) {
            return $this->_options[$name];
        }
        return null;
    }

    public function configRoutes()
    {
        $bootstrap = $this->getBootstrap();
        $router = $bootstrap->getResource('frontController')->getRouter();

        foreach ($this->_bootstraps as $moduleName => $moduleBootstrap) {
            $moduleOptions = $this->getOption($moduleName);

            // get routes defined by getRoutesConfig()
            if (method_exists($moduleBootstrap, 'getRouteConfig')) {
                $routesConfig = (array) $moduleBootstrap->getRouteConfig();
            } elseif (method_exists($moduleBootstrap, 'getRoutesConfig')) {
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
            if (isset($moduleOptions['routesConfig'])) {
                $routesConfig = $this->mergeOptions(
                    $routesConfig,
                    array_intersect_key($moduleOptions['routesConfig'], $routesConfig)
                );
            }
            if (is_array($routesConfig)) {
                $routesConfig = new Zend_Config($routesConfig);
            }
            if (!$routesConfig instanceof Zend_Config) {
                throw new InvalidArgumentException('Route config must be an instance of Zend_Config');
            }

            $router->addConfig($routesConfig);
        }
    }

    public function bootstrapModule($moduleName)
    {
        if (!isset($this->_bootstraps->{$moduleName})) {
            throw new Exception('Invalid module name: ' . $moduleName);
        }

        /** @var Zend_Application_Module_Bootstrap $moduleBootstrap */
        $moduleBootstrap = $this->_bootstraps->{$moduleName};

        if ($this->_modules[$moduleName]['state'] === self::STATE_BOOTSTRAPPED) {
            return $moduleBootstrap;
        }

        if ($this->_modules[$moduleName]['state'] === self::STATE_BOOTSTRAPPING) {
            throw new Exception('Cyclic module dependency detected; module ' . $moduleName . ' is during bootstrap process');
        }

        $this->_modules[$moduleName]['state'] = self::STATE_BOOTSTRAPPING;

        // bootstrap any built-in / plugin resources, they will be stored in
        // the common resource container
        $moduleBootstrap->bootstrap();

        // notify bootstrapping is complete
        if (method_exists($moduleBootstrap, 'onBootstrap')) {
            $moduleBootstrap->onBootstrap($this);
        }

        $this->_modules[$moduleName]['state'] = self::STATE_BOOTSTRAPPED;

        return $moduleBootstrap;
    }

    /**
     * @param  array $bootstraps
     * @return ArrayObject
     */
    protected function _executeBootstraps()
    {
        // at this point all module resources should be registered in bootstrap
        $this->configRoutes();

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


    protected function _hasBootstrapResource($resourceName)
    {
        $bootstrap = $this->getBootstrap();

        if ($bootstrap->hasResource($resourceName)) {
            // check if already initialized
            return true;
        }

        if ($bootstrap instanceof Zefram_Application_Bootstrap_Bootstrapper) {
            if ($bootstrap->hasClassResource($resourceName)) {
                return true;
            }
        } else {
            $bootstrapResources = array_flip(array_map('strtolower', $bootstrap->getClassResourceNames()));
            if (isset($bootstrapResources[strtolower($resourceName)])) {
                return true;
            }
        }

        if ($bootstrap instanceof Zend_Application_Bootstrap_ResourceBootstrapper) {
            return $bootstrap->hasPluginResource($resourceName);
        }

        return false;
    }
}
