<?php

/**
 * @version 2013-12-05
 */
class Maniple_Application_ServiceLocator
{
    /**
     * Bootstrap this service locator is attached to
     *
     * @var Zend_Application_Bootstrap_BootstrapAbstract
     */
    protected $_bootstrap;

    /**
     * Collection of registered services
     *
     * @var array
     */
    protected $_services = array();

    /**
     * @param array|object $options
     */
    public function __construct($options = null) // {{{
    {
        if (is_object($options) && method_exists($options, 'toArray')) {
            $options = (array) $options->toArray();
        }

        if (is_array($options)) {
            if (isset($options['bootstrap'])) {
                $this->setBootstrap($options['bootstrap']);
                unset($options['bootstrap']);
            }

            $this->addServices($options);
        }
    } // }}}

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
        switch (true) {
            case is_string($service):
                // string is considered a service's class name to be
                // instanciated if necessary
                $service = array(
                    'class' => $service,
                    'params' => null,
                );
                break;

            case is_array($service):
                // if a 'class' key is detected service is assumed to be
                // a service definition
                if (isset($service['class'])) {
                    $service = array_merge(array('params' => null), $service);
                }
                break;
        }

        $serviceName = strtolower($name);

        if (isset($this->_services[$serviceName])) {
            throw new Exception(sprintf(
                "Service '%s' is already registered", $name
            ));
        }

        $this->_services[$serviceName] = $service;
        return $this;
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
        $serviceName = strtolower($name);

        if (isset($this->_services[$serviceName])) {
            $service = $this->_services[$serviceName];

            if (is_array($service) && isset($service['class'])) {
                $service = $this->_loadService($service);
                $this->_services[$serviceName] = $service;
            }

            return $service;
        }

        throw new Exception("No service is registered for key '$name'");
    } // }}}

    /**
     * Create a service instance from array representation.
     *
     * @param  array $service
     * @return mixed
     * @throws Exception
     */
    protected function _loadService(array $service) // {{{
    {
        if (empty($service['class'])) {
            throw new Exception('No service class name provided');
        }

        $class = $service['class'];
        $params = $service['params'];

        // replace service placeholders with service instances
        if (is_object($params) && method_exists($params, 'toArray')) {
            $params = $params->toArray();
        }

        $params = (array) $params;

        // no cycle check for now
        foreach ($params as $key => $value) {
            if (is_string($value) && !strncasecmp($value, 'service:', 8)) {
                $params[$key] = $this->getService(substr($value, 8));
            }
        }

        return new $class($this, $params);
    } // }}}

    /**
     * Proxy to {@see getService()}.
     *
     * This function is typically called by Bootstrap when the service locator
     * is used as a resource container.
     *
     * @param  string $name
     * @return mixed
     */
    public function __get($name) // {{{
    {
        return $this->getService($name);
    } // }}}

    /**
     * Proxy to {@see addService()}.
     *
     * This function is typically called by Bootstrap when the service locator
     * is used as a resource container.
     *
     * @param  string $name
     * @param  mixed $service
     */
    public function __set($name, $service) // {{{
    {
        return $this->addService($name, $service);
    } // }}}

    /**
     * Is service of a given name defined.
     *
     * This function is typically called by Bootstrap when the service locator
     * is used as a resource container.
     *
     * @param  string $name
     * @return bool
     */
    public function __isset($name) // {{{
    {
        return isset($this->_services[strtolower($name)]);
    } // }}}

    /**
     * Unregister service of a given name.
     *
     * @param string $name
     */
    public function __unset($name) // {{{
    {
        unset($this->_services[strtolower($name)]);
    } // }}}
}
