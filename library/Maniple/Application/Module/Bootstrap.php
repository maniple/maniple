<?php

/**
 * Base class for module bootstrap.
 *
 * @version 2014-07-16
 * @author xemlock
 */
abstract class Maniple_Application_Module_Bootstrap extends Zend_Application_Module_Bootstrap
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
     * Array of module names this module depends on
     * @var string[]
     */
    protected $_moduleDeps;

    /**
     * Are all module dependencies bootstrapped?
     * @var bool
     */
    protected $_moduleDepsBootstrapped = false;

    /**
     * {@inheritdoc}
     *
     * @param  Zend_Application|Zend_Application_Bootstrap_Bootstrapper $application
     * @return Maniple_Application_Module_Bootstrap
     */
    public function setApplication($application) // {{{
    {
        parent::setApplication($application);

        // use same resource container as parent bootstrap
        $bootstrap = $this->getParentBootstrap();
        if ($bootstrap instanceof Zend_Application_Bootstrap_BootstrapAbstract) {
            $this->setContainer($bootstrap->getContainer());
        }

        return $this;
    } // }}}

    /**
     * Retrieves parent bootstrap.
     *
     * @return Zend_Application_Bootstrap_Bootstrapper|null
     */
    public function getParentBootstrap() // {{{
    {
        if ($this->_application instanceof Zend_Application) {
            $bootstrap = $this->_application->getBootstrap();
        } else {
            $bootstrap = $this->_application;
        }
        if ($bootstrap === $this) {
            return null;
        }
        return $bootstrap;
    } // }}}

    /**
     * Retrieves path to directory this bootstrap class resides in.
     *
     * @return string
     */
    public function getPath() // {{{
    {
        if (empty($this->_path)) {
            $ref = new ReflectionClass($this);
            $this->_path = dirname($ref->getFileName());
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
            throw new Exception('ModuleManager is not configured');
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
     * @param  null|string|array $resource
     * @return void
     */
    protected function _bootstrap($resource = null) // {{{
    {
        if (!$this->_moduleDepsBootstrapped) {
            foreach ((array) $this->_moduleDeps as $module) {
                $this->getModuleManager()->bootstrapModule($module);
            }
            $this->_moduleDepsBootstrapped = true;
        }
        parent::_bootstrap($resource);
    } // }}}
}
