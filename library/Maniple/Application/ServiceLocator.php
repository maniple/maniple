<?php

/**
 * @version 2013-12-05
 */
class Maniple_Application_ServiceLocator extends Maniple_Application_ResourceContainer
{
    /**
     * Bootstrap this service locator is attached to
     *
     * @var Zend_Application_Bootstrap_BootstrapAbstract
     */
    protected $_bootstrap;

    /**
     * Set the bootstrap to which this service locator is attached
     *
     * @param  Zend_Application_Bootstrap_Bootstrapper $bootstrap
     * @return Euhit_ServiceLocator
     * @deprecated
     */
    public function setBootstrap(Zend_Application_Bootstrap_Bootstrapper $bootstrap) // {{{
    {
        $this->_bootstrap = $bootstrap;
        return $this;
    } // }}}

    /**
     * Retrieve the bootstrap to which this service locator is attached
     *
     * @deprecated
     * @return Zend_Application_Bootstrap_Bootstrapper
     */
    public function getBootstrap() // {{{
    {
        if (empty($this->_bootstrap)) {
            throw new Exception("No bootstrap is available");
        }
        return $this->_bootstrap;
    } // }}}

    /**
     * Add many services at once
     *
     * @param  array $services
     * @return Euhit_ServiceLocator
     */
    public function addServices($services) // {{{
    {
        foreach ($services as $name => $service) {
            $this->addService($name, $service);
        }
        return $this;
    } // }}}

    /**
     * Add a service
     *
     * @param  string $name
     * @param  string|array|object $service
     * @return Euhit_ServiceLocator
     */
    public function addService($name, $service) // {{{
    {
        return $this->addResource($name, $service);
    } // }}}

    /**
     * Retrieve a service instance
     *
     * @param  string $name
     * @return mixed
     * @throws Exception
     */
    public function getService($name) // {{{
    {
        return $this->getResource($name);
    } // }}}
}
