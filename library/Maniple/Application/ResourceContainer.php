<?php

class Maniple_Application_ResourceContainer
{
    /**
     * Collection of registered resources
     *
     * @var array
     */
    protected $_resources = array();

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

            $this->addResources($options);
        }
    } // }}}

    /**
     * Add many services at once
     *
     * @param  array $services
     * @return Euhit_ServiceLocator
     */
    public function addResources($services) // {{{
    {
        foreach ($services as $name => $service) {
            $this->addResource($name, $service);
        }
        return $this;
    } // }}}

    /**
     * Add a service
     *
     * @param  string $name
     * @param  string|array|object $service
     * @return Euhit_ResourceLocator
     */
    public function addResource($name, $service) // {{{
    {
        switch (true) {
            case is_string($service):
                if (!strncasecmp($service, 'resource:', 9)) {
                    $service = new Maniple_Application_ResourceAlias(substr($service, 9));
                } else {
                    // string not begining with 'resource:' is considered to be
                    // a resource's class name
                    $service = array(
                        'class' => $service,
                        'params' => null,
                    );
                }
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

        if (isset($this->_resources[$serviceName])) {
            throw new Exception(sprintf(
                "Resource '%s' is already registered", $name
            ));
        }

        $this->_resources[$serviceName] = $service;
        return $this;
    } // }}}

    /**
     * Retrieve a service instance
     *
     * @param  string $name
     * @return mixed
     * @throws Exception
     */
    public function getResource($name) // {{{
    {
        $serviceName = strtolower($name);

        if (isset($this->_resources[$serviceName])) {
            $service = $this->_resources[$serviceName];

            switch (true) {
                case $service instanceof Maniple_Application_ResourceAlias:
                    // TODO cycle detection
                    $this->_resources[$serviceName] = $this->getResource($service->getTarget());
                    break;

                case is_array($service) && isset($service['class']):
                    $this->_resources[$serviceName] = $this->_loadResource($service);
                    break;
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
    protected function _loadResource(array $service) // {{{
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
                $params[$key] = $this->getResource(substr($value, 8));
            }
            if (is_string($value) && !strncasecmp($value, 'resource:', 9)) {
                $params[$key] = $this->getResource(substr($value, 9));
            }
        }

        $service = new $class($this, $params); // This way of service initialization is now deprecated
        $filter = new Zend_Filter_Word_UnderscoreToCamelCase();

        foreach ($params as $key => $value) {
            $methods = array(
                'set' . $filter->filter($key),
                'set' . $key
            );
            foreach ($methods as $method) {
                if (method_exists($service, $method)) {
                    $service->{$method}($value);
                    break;
                }
            }
        }

        return $service;
    } // }}}


    /**
     * Proxy to {@see getResource()}.
     *
     * This function is typically called by Bootstrap when the service locator
     * is used as a resource container.
     *
     * @param  string $name
     * @return mixed
     */
    public function __get($name) // {{{
    {
        return $this->getResource($name);
    } // }}}

    /**
     * Proxy to {@see addResource()}.
     *
     * This function is typically called by Bootstrap when the service locator
     * is used as a resource container.
     *
     * @param  string $name
     * @param  mixed $service
     */
    public function __set($name, $service) // {{{
    {
        return $this->addResource($name, $service);
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
        return isset($this->_resources[strtolower($name)]);
    } // }}}

    /**
     * Unregister service of a given name.
     *
     * @param string $name
     */
    public function __unset($name) // {{{
    {
        unset($this->_resources[strtolower($name)]);
    } // }}}
}
