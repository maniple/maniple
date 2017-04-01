<?php

/**
 * Base class for module bootstrap.
 *
 * @version 2014-07-16
 * @author xemlock
 */
abstract class Maniple_Application_Module_Bootstrap
    extends Zefram_Application_Module_Bootstrap
{
    /**
     * @var string
     */
    protected $_path;

    /**
     * Manager for module dependency management
     * @var Maniple_Application_ModuleBootstrapper
     * @deprecated
     */
    protected $_moduleManager;

    /**
     * Names of modules this module depends on
     * @var string[]
     * @deprecated Implement getModuleDependencies() instead
     */
    protected $_moduleDeps = array();

    /**
     * Are all module dependencies bootstrapped?
     * @var bool
     * @deprecated
     */
    protected $_moduleDepsBootstrapped = false;

    /**
     * Names of module tasks to be executed after all local resources are bootstrapped
     * @var string[]
     * @deprecated
     */
    protected $_moduleTasks;

    /**
     * Constructor.
     *
     * @param  Zend_Application|Zend_Application_Bootstrap_Bootstrapper $application
     * @return void
     */
    public function __construct($application) // {{{
    {
        parent::__construct($application);

        // front controller will be registered when necessary, as it
        // may be already present in the resource container
        $this->unregisterPluginResource('FrontController');
    } // }}}

    /**
     * Retrieves parent bootstrap.
     *
     * @return Zend_Application_Bootstrap_Bootstrapper|null
     * @deprecated Use {@link getApplication()} instead
     */
    public function getParentBootstrap() // {{{
    {
        return $this->getApplication();
    } // }}}

    /**
     * Retrieves path to directory this bootstrap class resides in.
     *
     * @param  string $path OPTIONAL
     * @return string
     * @deprecated
     */
    public function getPath($path = null) // {{{
    {
        if (empty($this->_path)) {
            $ref = new ReflectionClass($this);
            $this->_path = dirname($ref->getFileName());
        }
        if ($path !== null) {
            $path = str_replace(array('\\', '/'), DIRECTORY_SEPARATOR, $path);
            $path = ltrim($path, DIRECTORY_SEPARATOR);
            if (strlen($path)) {
                return $this->_path . DIRECTORY_SEPARATOR . $path;
            }
        }
        return $this->_path;
    } // }}}

    /**
     * Sets module manager.
     *
     * @param  Maniple_Application_ModuleBootstrapper $moduleManager
     * @return Maniple_Application_Module_Bootstrap
     * @deprecated
     */
    public function setModuleManager(Maniple_Application_ModuleBootstrapper $moduleManager) // {{{
    {
        $this->_moduleManager = $moduleManager;
        $this->_moduleDepsBootstrapped = false;
        return $this;
    } // }}}

    /**
     * Retrieves module manager.
     *
     * @return Maniple_Application_ModuleBootstrapper
     * @throws Exception
     * @deprecated
     */
    public function getModuleManager() // {{{
    {
        if (empty($this->_moduleManager)) {
            throw new Exception('Module manager is not configured');
        }
        return $this->_moduleManager;
    } // }}}

    /**
     * Is module manager available?
     *
     * @return bool
     * @deprecated
     */
    public function hasModuleManager() // {{{
    {
        return (bool) $this->_moduleManager;
    } // }}}

    /**
     * {@inheritdoc}
     *
     * Additionally this method ensures that all module dependencies are
     * resolved and bootstrapped prior to bootstrapping local resources.
     *
     * As the last stage of module bootstrapping all registered helpers
     * are executed.
     *
     * @param  null|string|array $resource
     * @return void
     */
    protected function _bootstrap($resource = null) // {{{
    {
        // ensure front controller resource is registered
        if ($resource === null && !$this->hasResource('FrontController')) {
            $this->registerPluginResource('FrontController');
        }

        // ensure all dependencies are bootstrapped before bootstrapping
        // any local resources
        $this->_bootstrapModuleDeps();

        parent::_bootstrap($resource);

        // run initialization tasks after all resources are bootstrapped
        if ($resource === null) {
            $this->_runModuleTasks();
        }
    } // }}}

    /**
     * Bootstrap module dependencies.
     *
     * @return void
     * @deprecated
     */
    protected function _bootstrapModuleDeps() // {{{
    {
        if (!$this->hasModuleManager()) {
            return;
        }
        if (!$this->_moduleDepsBootstrapped) {
            foreach ((array) $this->getModuleDependencies() as $module) {
                $this->getModuleManager()->bootstrapModule($module);
            }
            $this->_moduleDepsBootstrapped = true;
        }
    } // }}}

    /**
     * Run module tasks.
     *
     * @return void
     */
    protected function _runModuleTasks() // {{{
    {
        if (!$this->hasModuleManager()) {
            return;
        }
        if ($this->_moduleTasks) {
            trigger_error(sprintf('%s::_moduleTasks is deprecated. Use modules resource instead', get_class($this)), E_USER_NOTICE);
        }
        foreach ((array) $this->_moduleTasks as $task) {
            $this->getModuleManager()->runTask($task, $this);
        }
    } // }}}

    /**
     * Return an array of module names on which this module depends on
     *
     * @return string[]
     */
    public function getModuleDependencies()
    {
        trigger_error(sprintf('%s::_moduleDeps is deprecated. Each module should override getModuleDependencies() function', get_class($this)), E_USER_NOTICE);
        return $this->_moduleDeps;
    }

    /**
     * Register routes in router using definitions from getResourcesConfig()
     *
     * @return void
     */
    protected function _initRouter()
    {
        if (!method_exists($this, 'getRoutesConfig')) {
            return;
        }

        $routesConfig = (array) $this->getRoutesConfig();

        /** @var Zend_Application_Bootstrap_BootstrapAbstract $bootstrap */
        $bootstrap = $this->getApplication();
        $bootstrap->bootstrap('FrontController');

        /** @var Zend_Controller_Router_Rewrite $router */
        $router = $bootstrap->getResource('FrontController')->getRouter();
        $router->addConfig(new Zend_Config($routesConfig));
    }

    /**
     * Add translations specified in getTranslationsConfig()
     *
     * @return void
     */
    protected function _initTranslate()
    {
        if (!method_exists($this, 'getTranslationsConfig')) {
            return;
        }

        $bootstrap = $this->getApplication();
        $bootstrap->bootstrap('Translate');

        /** @var Zend_Translate_Adapter $translate */
        $translate = $bootstrap->getResource('Translate')->getAdapter();

        $translationsConfig = (array) $this->getTranslationsConfig();

        if (!isset($translationsConfig['adapter'])) {
            $translationsConfig['adapter'] = Zend_Translate::AN_ARRAY;
        }

        // When locale was not explicitly set, use the locale of Translate
        // resource, to prevent unnecessary automatic locale detection,
        // which may result in 'The language has to be added before it can
        // be used' notices.
        if (!isset($translationsConfig['locale'])) {
            $translationsConfig['locale'] = $translate->getLocale();
        }

        $translations = new Zend_Translate($translationsConfig);

        // Prevent 'Undefined index' notice when there are no translations
        // available for current locale.
        if ($translations->isAvailable($translate->getLocale())) {
            $translate->addTranslation($translations);
        }
    }

    /**
     * Register view script and helper paths based on getViewConfig()
     *
     * @return void
     */
    protected function _initView()
    {
        if (!method_exists($this, 'getViewConfig')) {
            return;
        }

        $viewConfig = (array) $this->getViewConfig();

        /** @var Zend_Application_Bootstrap_BootstrapAbstract $bootstrap */
        $bootstrap = $this->getApplication();
        $bootstrap->bootstrap('View');

        /** @var Zend_View_Abstract $view */
        $view = $bootstrap->getResource('View');

        if (isset($viewConfig['helperPaths'])) {
            foreach ((array) $viewConfig['helperPaths'] as $prefix => $path) {
                $view->addHelperPath($path, $prefix);
            }
        }

        if (isset($viewConfig['scriptPaths'])) {
            foreach ((array) $viewConfig['scriptPaths'] as $scriptPath) {
                $view->addScriptPath($scriptPath);
            }
        }
    }
}
