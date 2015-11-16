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
     */
    protected $_moduleManager;

    /**
     * Names of modules this module depends on
     * @var string[]
     */
    protected $_moduleDeps;

    /**
     * Are all module dependencies bootstrapped?
     * @var bool
     */
    protected $_moduleDepsBootstrapped = false;

    /**
     * Names of module tasks to be executed after all local resources are bootstrapped
     * @var string[]
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

        // front controller will be registered when neccessary, as it
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
        // ensure all dependencies are bootstrapped before bootstrapping
        // any local resources
        $this->_bootstrapModuleDeps();

        // ensure front controller resource is registered
        if ($resource === null && !$this->hasResource('FrontController')) {
            $this->registerPluginResource('FrontController');
        }

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
     */
    protected function _bootstrapModuleDeps() // {{{
    {
        if (!$this->_moduleDepsBootstrapped) {
            foreach ((array) $this->_moduleDeps as $module) {
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
        foreach ((array) $this->_moduleTasks as $task) {
            $this->getModuleManager()->runTask($task, $this);
        }
    } // }}}
}
