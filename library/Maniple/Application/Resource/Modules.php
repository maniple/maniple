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
 * Put this as the first resource, to achieve desired effect.
 *
 * @version 2014-07-13
 */
class Maniple_Application_Resource_Modules
    extends Zend_Application_Resource_ResourceAbstract
    implements Maniple_Application_ModuleBootstrapper
{
    const STATE_DEFAULT       = 0;
    const STATE_BOOTSTRAPPING = 1;
    const STATE_BOOTSTRAPPED  = 2;

    /**
     * Module paths registered in front controller
     * @var array
     */
    protected $_modulePaths;

    /**
     * Loaded modules data
     * @var array
     */
    protected $_loadedModules = array();

    /**
     * Used for resolving bootstraping order
     * @var array
     */
    protected $_stackIndexCounter = 0;

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
            return $this;
        }

        $this->_initModules();
        $this->_initAutoloader();

        $this->configResources();

        $this->_configured = true;
        return $this;
    }

    public function getModulesDirectory()
    {
        /** @var Zend_Application_Bootstrap_BootstrapAbstract $bootstrap */
        $bootstrap = $this->getBootstrap();
        /** @var Zend_Controller_Front $front */
        $front = $bootstrap->getResource('FrontController');
        $modulesDir = array_values(array_unique(array_map('dirname', array_map('dirname', $front->getControllerDirectory()))));
        return $modulesDir;
    }

    /**
     * Builds a list of available modules.
     */
    protected function _initModules()
    {
        /** @var Zend_Application_Bootstrap_BootstrapAbstract $bootstrap */
        $bootstrap = $this->getBootstrap();
        $bootstrap->bootstrap('FrontController');

        /** @var $front Zend_Controller_Front */
        $front = $bootstrap->getResource('FrontController');

        // Module detection (in Zend_Controller_Front::addModuleDirectory())
        // requires that each module contains controllers/ directory.
        $this->_modulePaths = array_map('dirname', $front->getControllerDirectory());

        // Prepare a list of modules to load
        // don't load modules explicitly given as FALSE
        $modulePaths = $this->_modulePaths;

        foreach ($this->getOptions() as $module => $value) {
            if ($value === false) {
                unset($modulePaths[$module]);
            }
        }
        $toLoad = array_keys($modulePaths);

        // load modules
        foreach ($toLoad as $module) {
            $this->_loadModule($module);
        }

        $this->_sortLoadedModules();

        $this->_bootstraps = new ArrayObject(array(), ArrayObject::ARRAY_AS_PROPS);
    }

    /**
     * List of module names not yet processed
     * @var
     */
    protected $_queue = array();

    protected function _sortLoadedModules()
    {
        while ($module = array_shift($this->_queue)) {
            $this->_depVisitor($module);
        }
        uasort($this->_loadedModules, array($this, '_sortLoadedModulesCompare'));
    }

    /**
     * Load a specific module by name.
     *
     * @param string $module
     * @return object
     * @throws Zend_Application_Bootstrap_Exception
     * @throws Zend_Application_Resource_Exception
     */
    public function _loadModule($module)
    {
        if (isset($this->_loadedModules[$module])) {
            return $this->_loadedModules[$module];
        }

        /** @var Zend_Application_Bootstrap_BootstrapAbstract $bootstrap */
        $bootstrap = $this->getBootstrap();
        $bootstrap->bootstrap('FrontController');

        /** @var $front Zend_Controller_Front */
        $front = $bootstrap->getResource('FrontController');
        $default = $front->getDefaultModule();

        $modulePrefix = $this->_formatModuleName($module);
        $bootstrapClass = $modulePrefix . '_Bootstrap';

        $curBootstrapClass = get_class($this->getBootstrap());

        // use autoloading - so that modules residing in other locations, but accessible
        // to autoloader can be taken into account
        if (class_exists($bootstrapClass, true)) {
            try {
                $ref = new ReflectionClass($bootstrapClass);
                $modulePaths[$module] = $modulePath = dirname($ref->getFileName());
            } catch (Exception $e) {
                throw new Zend_Application_Resource_Exception($e->getMessage(), $e->getCode(), $e);
            }

        } else {
            if (isset($this->_modulePaths[$module])) {
                $modulePath = $this->_modulePaths[$module];
            } else {
                foreach ($this->getModulesDirectory() as $dir) {
                    if (is_dir($dir . '/' . $module)) {
                        $modulePath = $dir . '/' . $module;
                        break;
                    }
                }
            }
            if (empty($modulePath) || !is_dir($modulePath)) {
                throw new Zend_Application_Resource_Exception(sprintf(
                    'Unable to find directory for module "%s"', $module
                ));
            }

            // support for legacy modules whose files reside in the module/ subdirectory
            // if controllers directory exists, add it to front controller, as
            // otherwise it would not be found by the dispatcher
            if (file_exists($modulePath . '/module')) {
                $modulePath .= '/module';
            }

            $bootstrapPath  = $modulePath . '/Bootstrap.php';
            if (file_exists($bootstrapPath)) {
                $eMsgTpl = 'Bootstrap file found for module "%s" but bootstrap class "%s" not found';
                /** @noinspection PhpIncludeInspection */
                include_once $bootstrapPath;
                if (($default != $module)
                    && !class_exists($bootstrapClass, false)
                ) {
                    throw new Zend_Application_Resource_Exception(sprintf(
                        $eMsgTpl, $module, $bootstrapClass
                    ));
                } elseif ($default === $module) {
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
                return null;
            }
        }

        if ($bootstrapClass === $curBootstrapClass) {
            // If the found bootstrap class matches the one calling this
            // resource don't re-execute, but add to stack so that any
            // dependencies can be properly handled
            $moduleBootstrap = $this->getBootstrap();
            $state = self::STATE_BOOTSTRAPPED;
        } else {
            $moduleBootstrap = new $bootstrapClass($this->getBootstrap());
            $state = self::STATE_DEFAULT;
        }

        // this feature is deprecated, use getModuleDependencies() instead
        // of bootstrapping modules explicitly
        if ($moduleBootstrap instanceof Maniple_Application_Module_Bootstrap) {
            $moduleBootstrap->setModuleManager($this);
        }

        // add module controllers/ directory without checking if it really exists
        // - the way a module directory is retrieved from front controller
        // depends on whether module's controller directory is added to
        // dispatcher (not the module directory itself), regardless of its existence
        try {
            $front->addControllerDirectory($modulePath . '/controllers', $module);
        } catch (Zend_Controller_Exception $e) {
            throw new Zend_Application_Resource_Exception($e->getMessage(), $e->getCode(), $e);
        }

        $moduleData = (object) array(
            'name'           => $module,
            'prefix'         => $modulePrefix,
            'path'           => $modulePath,
            'bootstrap'      => $moduleBootstrap,
            'bootstrapClass' => $bootstrapClass,
            'state'          => $state,
            'stackIndex'     => null,
            'dependencies'   => method_exists($moduleBootstrap, 'getModuleDependencies')
                ? (array) $moduleBootstrap->getModuleDependencies()
                : array(),
        );

        $this->_loadedModules[$module] = $moduleData;
        $this->_queue[] = $module;

        foreach ($moduleData->dependencies as $dep) {
            $this->_loadModule($dep);
        }

        return $moduleData;
    }

    /**
     * Load a specific module by name.
     *
     * @param string $module
     * @return Zend_Application_Bootstrap_BootstrapAbstract
     * @throws Zend_Application_Bootstrap_Exception
     * @throws Zend_Application_Resource_Exception
     */
    public function loadModule($module)
    {
        if (null !== ($moduleData = $this->_loadModule($module))) {
            return $moduleData->bootstrap;
        }
        throw new Zend_Application_Resource_Exception(sprintf("Unable to load module '%s'", $module));
    }

    /**
     * @param object $a
     * @param object $b
     * @return int
     * @internal
     */
    protected function _sortLoadedModulesCompare($a, $b)
    {
        // sort descending by stackIndex
        return $a->stackIndex - $b->stackIndex;
    }

    function _depVisitor($module)
    {
        $moduleData = $this->_loadModule($module);
        if (!$moduleData) {
            throw new Exception('Unable to load module: ' . $module);
        }
        if (@$moduleData->depSortState === 'done') {
            return;
        }
        if (@$moduleData->depSortState === 'processing') {
            throw new Exception('Circular module dependency detected on module: ' . $module);
        }
        $moduleData->depSortState = 'processing';
        foreach ($moduleData->dependencies as $name) {
            $this->_depVisitor($name);
        }
        $moduleData->depSortState = 'done';
        $moduleData->stackIndex = ++$this->_stackIndexCounter;
    }

    /**
     * Initializes autoloader for module classes.
     *
     * @return void
     */
    protected function _initAutoloader()
    {
        $autoloaderConfig = array();

        // add libary dir to include path, see:
        // http://stackoverflow.com/questions/13377983/zend-framework-module-library
        foreach ($this->_loadedModules as $module => $moduleInfo) {
            $moduleBootstrap = $moduleInfo->bootstrap;

            // method_exists checks is method is defined in the code
            // is_callable if it's callable in the current context. Both
            // need to be checked, because is_callable returns true if __call
            // magic method is defined
            if (method_exists($moduleBootstrap, 'getAutoloaderConfig')
                && is_callable(array($moduleBootstrap, 'getAutoloaderConfig'))
            ) {
                $autoloaderConfig = Zefram_Stdlib_ArrayUtils::merge(
                    $autoloaderConfig,
                    $moduleBootstrap->getAutoloaderConfig()
                );
            }
        }

        if ($autoloaderConfig) {
            if (isset($autoloaderConfig['prefixes'])) { // legacy format
                $prefixes = $autoloaderConfig['prefixes'];
                unset($autoloaderConfig['prefixes']);
            } else {
                $prefixes = array();
            }

            $autoloaderConfig = Zefram_Stdlib_ArrayUtils::merge(
                array('Zend_Loader_StandardAutoloader' => array(
                    'prefixes' => $prefixes,
                )),
                $autoloaderConfig
            );

            Zend_Loader_AutoloaderFactory::factory($autoloaderConfig);
        }
    }

    public function configResources()
    {
        /** @var Zend_Application_Bootstrap_BootstrapAbstract $bootstrap */
        $bootstrap = $this->getBootstrap();
        $resources = array();

        // TODO use module configs from bootstrap
        foreach ($this->_loadedModules as $moduleName => $moduleInfo) {
            $moduleBootstrap = $moduleInfo->bootstrap;
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

        $resources = array_change_key_case($resources, CASE_LOWER);

        $this->_resourceConfig = $resources;
        foreach ($resources as $resource => $resConfig) {
            $container = $bootstrap->getContainer();
            if (!isset($container->{$resource})) {
                $container->{$resource} = $resConfig;
            }
        }

        // echo '<pre>';print_r($resources);exit;
    }

    /**
     * @var array
     */
    protected $_resourceConfig;

    public function getResourceConfig()
    {
        return $this->_resourceConfig;
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

    public function configRoutes($moduleInfo)
    {
        /** @var Zend_Application_Bootstrap_BootstrapAbstract $bootstrap */
        $bootstrap = $this->getBootstrap();
        /** @var Zend_Controller_Router_Rewrite $router */
        $router = $bootstrap->getResource('frontController')->getRouter();

        $moduleBootstrap = $moduleInfo->bootstrap;
        $moduleOptions = $this->getOption($moduleInfo->name);

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

    public function bootstrapModule($moduleName)
    {
        if (!isset($this->_loadedModules[$moduleName])) {
            throw new Zend_Application_Resource_Exception('Invalid module name: ' . $moduleName);
        }

        $moduleInfo = $this->_loadedModules[$moduleName];

        /** @var Zend_Application_Module_Bootstrap $moduleBootstrap */
        $moduleBootstrap = $moduleInfo->bootstrap;

        if ($moduleInfo->state === self::STATE_BOOTSTRAPPED) {
            return $moduleBootstrap;
        }

        if ($moduleInfo->state === self::STATE_BOOTSTRAPPING) {
            throw new Zend_Application_Resource_Exception('Cyclic module dependency detected; module ' . $moduleName . ' is during bootstrap process');
        }

        $moduleInfo->state = self::STATE_BOOTSTRAPPING;

        foreach ($moduleInfo->dependencies as $dependency) {
            $this->bootstrapModule($dependency);
        }

        // add module routes to router
        $this->configRoutes($moduleInfo);

        // bootstrap any built-in / plugin resources, they will be stored in
        // the common resource container
        $moduleBootstrap->bootstrap();

        // notify bootstrapping is complete - here, not after all modules are bootstrapped
        // due to BC
        if (method_exists($moduleBootstrap, 'onBootstrap')) {
            $moduleBootstrap->onBootstrap($this);
        }

        $moduleInfo->state = self::STATE_BOOTSTRAPPED;
        $this->_bootstraps[$moduleName] = $moduleBootstrap;

        return $moduleBootstrap;
    }

    protected function _executeBootstraps()
    {
        /** @var $front Zend_Controller_Front */
        $front = $this->getBootstrap()->getResource('FrontController');
        $defaultModule = $front->getDefaultModule();

        // Start bootstrapping with default module
        if ($defaultModule) {
            $this->bootstrapModule($defaultModule);
        }

        foreach ($this->_loadedModules as $module => $moduleInfo) {
            $this->bootstrapModule($module);
        }

        // great, all modules are loaded, notify of module loading completion
        // BC
        foreach ($this->_bootstraps as $moduleBootstrap) {
            if (method_exists($moduleBootstrap, 'onModulesLoaded')) {
                $moduleBootstrap->onModulesLoaded($this);
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
     * @deprecated
     */
    protected $_taskLoader;

    /**
     * @var Maniple_Application_Module_Task_TaskInterface[]
     * @deprecated
     */
    protected $_taskRegistry;

    /**
     * @deprecated
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
     * @return mixed
     * @deprecated
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
