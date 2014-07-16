<?php

/**
 * Base class for module bootstrap.
 *
 * @version 2014-07-16
 * @author xemlock
 */
class Maniple_Application_Module_Bootstrap extends Zend_Application_Module_Bootstrap
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
     * Set parent bootstrap.
     *
     * @param  Maniple_Application_Bootstrap_Bootstrap $application
     * @return Maniple_Application_Module_Bootstrap
     * @throws Zend_Application_Bootstrap_Exception
     */
    public function setApplication($application) // {{{
    {
        if (!$application instanceof Maniple_Application_Bootstrap_Bootstrap) {
            throw new Zend_Application_Bootstrap_Exception('Invalid application provided, expected an instance of Maniple_Application_Bootstrap_Bootstrap, received ' . get_class($application));
        }
        return parent::setApplication($application);
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
     * @param Maniple_Application_ModuleBootstrapper $moduleManager
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
