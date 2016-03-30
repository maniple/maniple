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
    extends Maniple_Application_Resource_ResourceAbstract
    implements Maniple_Application_ModuleBootstrapper
{
    const STATE_DEFAULT       = 0;
    const STATE_BOOTSTRAPPING = 1;
    const STATE_BOOTSTRAPPED  = 2;

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

        $this->configResources();

        $this->_configured = true;
        return $this;
    }

    public function getModulesDirectory()
    {
        /** @var Zend_Controller_Front $front */
        $front = $this->getBootstrap()->getResource('FrontController');
        $modulesDir = array_values(array_unique(array_map('dirname', array_map('dirname', $front->getControllerDirectory()))));
        return $modulesDir;
    }

    /**
     * Builds a list of available modules.
     */
    protected function _initModules()
    {
        $bootstrap = $this->getBootstrap();
        $bootstrap->bootstrap('FrontController');

        /** @var $front Zend_Controller_Front */
        $front = $bootstrap->getResource('FrontController');


        $modulePaths = array();

        foreach ($this->getOptions() as $key => $value) {
            if (is_string($value)) {
                $modulePaths[$value] = null;
            } elseif (is_string($key)) {
                $modulePaths[$key] = null;
            }
        }
        if (!$modulePaths) {
            // Module detection (in Zend_Controller_Front::addModuleDirectory())
            // requires that each module contains controllers/ directory.
            $modulePaths = array_map('dirname', $front->getControllerDirectory());
        }

        $default = $front->getDefaultModule();
        $curBootstrapClass = get_class($bootstrap);
        $bootstraps = array();

        foreach ($modulePaths as $module => $modulePath) {
            $modulePrefix = $this->_formatModuleName($module);
            $bootstrapClass = $modulePrefix . '_Bootstrap';

            // use autoloading - so that modules residing in other locations, but accessible
            // to autoloader can be taken into account
            if (class_exists($bootstrapClass, true)) {
                if ($modulePath === null) {
                    $ref = new ReflectionClass($bootstrapClass);
                    $modulePaths[$module] = $modulePath = dirname($ref->getFileName());
                }
            } else {
                if ($modulePath === null) {
                    foreach ($this->getModulesDirectory() as $dir) {
                        if (is_dir($dir . '/' . $module)) {
                            $modulePath = $dir . '/' . $module;
                            $modulePaths[$module] = $modulePath;
                            break;
                        }
                    }
                }
                if (!is_dir($modulePath)) {
                    throw new Exception(sprintf(
                        'Unable to find directory for module "%s"', $module
                    ));
                }

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
                    continue;
                }
            }

            if ($bootstrapClass === $curBootstrapClass) {
                // If the found bootstrap class matches the one calling this
                // resource, don't re-execute.
                continue;
            }

            $moduleBootstrap = new $bootstrapClass($this->getBootstrap());

            if ($moduleBootstrap instanceof Maniple_Application_Module_Bootstrap) {
                $moduleBootstrap->setModuleManager($this);
            }

            // add controllers directory without checking if it exists
            // - the way a module directory is retrieved from front controller
            // depends on whether module's controller directory is added to
            // dispatcher (not the module directory itself)
            $front->addControllerDirectory($modulePath . '/controllers', $module);

            $bootstraps[$module] = array(
                'prefix' => $modulePrefix,
                'path' => $modulePath,
                'bootstrap' => $moduleBootstrap,
                'bootstrapClass' => $bootstrapClass,
                'state' => self::STATE_DEFAULT,
            );
        }

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
        // add libary dir to include path, see:
        // http://stackoverflow.com/questions/13377983/zend-framework-module-library
        foreach ($this->_modules as $module => $moduleInfo) {
            $path = $moduleInfo['path'] . '/library';

            if (is_dir($path)) {
                Zend_Loader_AutoloaderFactory::factory(array(
                    'Zend_Loader_StandardAutoloader' => array(
                        'prefixes' => array(
                            $moduleInfo['prefix'] . '_' => $path,
                        ),
                    ),
                ));
            }
        }
    } // }}}

    public function configResources()
    {
        $bootstrap = $this->getBootstrap();
        $resources = array();

        // TODO use module configs from bootstrap
        foreach ($this->_modules as $moduleName => $moduleInfo) {
            $moduleBootstrap = $moduleInfo['bootstrap'];
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
            $container = $this->getBootstrap()->getContainer();
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

    public function configRoutes()
    {
        $bootstrap = $this->getBootstrap();
        $router = $bootstrap->getResource('frontController')->getRouter();

        foreach ($this->_modules as $moduleName => $moduleInfo) {
            $moduleBootstrap = $moduleInfo['bootstrap'];
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
        if (!isset($this->_modules[$moduleName])) {
            throw new Exception('Invalid module name: ' . $moduleName);
        }

        /** @var Zend_Application_Module_Bootstrap $moduleBootstrap */
        $moduleBootstrap = $this->_modules[$moduleName]['bootstrap'];

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

        // notify bootstrapping is complete - here, not after all modules are bootstrapped
        // due to BC
        if (method_exists($moduleBootstrap, 'onBootstrap')) {
            $moduleBootstrap->onBootstrap($this);
        }

        $this->_modules[$moduleName]['state'] = self::STATE_BOOTSTRAPPED;
        $this->_bootstraps{$moduleName} = $moduleBootstrap;

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
