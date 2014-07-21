<?php

/**
 * Resource container with lazy object initialization.
 *
 * @version 2014-07-21
 */
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
            $this->addResources($options);
        }
    } // }}}

    /**
     * Add many services at once
     *
     * @param  array|Traversable $services
     * @return Maniple_Application_ResourceContainer
     */
    public function addResources($resources) // {{{
    {
        foreach ($resources as $name => $resource) {
            $this->addResource($name, $resource);
        }
        return $this;
    } // }}}

    /**
     * Add a resource
     *
     * @param  string $name
     * @param  string|array|object $resource
     * @return Maniple_Application_ResourceContainer
     */
    public function addResource($name, $resource) // {{{
    {
        switch (true) {
            case is_string($resource):
                if (!strncasecmp($resource, 'resource:', 9)) {
                    $resource = new Maniple_Application_ResourceAlias(substr($resource, 9));
                } else {
                    // string not begining with 'resource:' is considered to be
                    // a class name
                    $resource = array(
                        'class'   => $resource,
                        'params'  => null,
                        'options' => null,
                    );
                }
                break;

            case is_array($resource):
                // if a 'class' key is detected service is assumed to be
                // a service definition
                if (isset($resource['class'])) {
                    $resource = array_merge(array(
                        'params'  => null,
                        'options' => null,
                    ), $resource);
                }
                break;
        }

        $resourceName = strtolower($name);

        if (isset($this->_resources[$resourceName])) {
            throw new Exception(sprintf(
                "Resource '%s' is already registered", $name
            ));
        }

        $this->_resources[$resourceName] = $resource;
        return $this;
    } // }}}

    /**
     * Retrieve a resource instance
     *
     * @param  string $name
     * @return mixed
     * @throws Exception
     */
    public function getResource($name) // {{{
    {
        $resourceName = strtolower($name);

        if (isset($this->_resources[$resourceName])) {
            $resource = $this->_resources[$resourceName];

            switch (true) {
                case $resource instanceof Maniple_Application_ResourceAlias:
                    // resolve resource alias
                    return $this->_resources[$resourceName] = $this->getResource($resource->getTarget());

                case is_array($resource) && isset($resource['class']):
                    return $this->_resources[$resourceName] = $this->_createInstance($resource);

                default:
                    return $resource;
            }
        }

        throw new Exception("No resource is registered for key '$name'");
    } // }}}

    /**
     * @return array
     */
    protected function _prepareParams($params)
    {
        if (is_object($params) && method_exists($params, 'toArray')) {
            $params = $params->toArray();
        }

        $params = (array) $params;

        foreach ($params as $key => $value) {
            if (is_string($value) && !strncasecmp($value, 'resource:', 9)) {
                $params[$key] = $this->getResource(substr($value, 9));
            }
            // recursively replace arrays with 'class' key with instances of
            // matching classes
            if (is_array($value)) {
                if (isset($value['class'])) {
                    $params[$key] = $this->_createInstance($value);
                } else {
                    $params[$key] = $this->_prepareParams($value);
                }
            }
        }

        return $params;
    }

    /**
     * Create an instance of a given class and setup its parameters.
     *
     * @param  string $class
     * @param  array $params OPTIONAL
     * @return object
     */
    protected function _createInstance(array $description) // {{{
    {
        if (empty($description['class'])) {
            throw new InvalidArgumentException('No class name found in description');
        }

        $class = $description['class'];
        $params = null;

        if (isset($description['params'])) {
            $params = $this->_prepareParams($description['params']);
        }

        $instance = new $class();

        // this is now deprecated. Params will be passed to constructor
        foreach ((array) $params as $key => $value) {
            $methods = array(
                'set' . str_replace('_', '', $key),
                'set' . $key
            );
            foreach ($methods as $method) {
                if (method_exists($instance, $method)) {
                    $instance->{$method}($value);
                    break;
                }
            }
        }

        // Set options using setter methods, try camel-cased versions
        // first, then underscored. Because PHP is case-insensitive when
        // it comes to function names, there is no need to appy some fancy
        // underscore-to-camel-case filter. Removing all underscore is
        // sufficient.
        if (isset($description['options'])) {
            $options = $this->_prepareParams($description['options']);

            foreach ($options as $key => $value) {
                $methods = array(
                    'set' . str_replace('_', '', $key),
                    'set' . $key
                );
                foreach ($methods as $method) {
                    if (method_exists($instance, $method)) {
                        $instance->{$method}($value);
                        break;
                    }
                }
            }
        }

        return $instance;
    } // }}}

    /**
     * Proxy to {@see getResource()}.
     *
     * This function is expected to be called by Bootstrap.
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
     * This function is expected to be called by Bootstrap.
     *
     * @param  string $name
     * @param  mixed $resource
     */
    public function __set($name, $resource) // {{{
    {
        return $this->addResource($name, $resource);
    } // }}}

    /**
     * Is resource of a given name defined.
     *
     * This function is expected to called by Bootstrap.
     *
     * @param  string $name
     * @return bool
     */
    public function __isset($name) // {{{
    {
        return isset($this->_resources[strtolower($name)]);
    } // }}}

    /**
     * Unregister resource of a given name.
     *
     * @param string $name
     */
    public function __unset($name) // {{{
    {
        unset($this->_resources[strtolower($name)]);
    } // }}}
}
