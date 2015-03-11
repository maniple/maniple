<?php

/**
 * Resource container with lazy object initialization.
 *
 * @version 2015-03-11 / 2014-07-21
 */
class Maniple_Application_ResourceContainer
    extends Zefram_Application_ResourceContainer
{
    protected $_readyCallbacks = array();

    /**
     * Add a resource
     *
     * @param  string $name
     * @param  string|array|object $resource
     * @return Maniple_Application_ResourceContainer
     */
    public function addResource($name, $resource) // {{{
    {
        return parent::addResource(strtolower($name), $resource);
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
        $name = strtolower($name);
        $ready = $this->isReady($name);
        $resource = parent::getResource($name);
        if (!$ready) {
            $this->_fireReadyCallbacks($name, $resource);
        }
        return $resource;
    } // }}}

    /**
     * Remove resource from container.
     *
     * @param  string $resourceName
     * @return Maniple_Application_ResourceContainer
     */
    public function removeResource($name) // {{{
    {
        return parent::removeResource(strtolower($name));
    } // }}}

    /**
     * Is given resource registered in the container?
     *
     * @param  string $resourceName
     * @return bool
     */
    public function hasResource($name) // {{{
    {
        return parent::hasResource(strtolower($name));
    } // }}}

    /**
     * @param  string $resourceName
     * @param  mixed $resource
     * @return void
     */
    protected function _fireReadyCallbacks($resourceName, $resource) // {{{
    {
        if (isset($this->_readyCallbacks[$resourceName])) {
            foreach ($this->_readyCallbacks[$resourceName] as $callback) {
                call_user_func($callback, $resource, $this);
            }
            unset($this->_readyCallbacks[$resourceName]);
        }
    } // }}}

    /**
     * Is resource of a given name initialized?
     *
     * @param  string $resourceName
     * @return bool
     */
    public function isReady($resourceName) // {{{
    {
        if (isset($this->_resources[$resourceName]) ||
            array_key_exists($resourceName, $this->_resources)
        ) {
            return true;
        }

        if (isset($this->_aliases[$resourceName])) {
            return $this->isReady($this->_aliases[$resourceName]);
        }

        return false;
    } // }}}

    /**
     * Register initialization callback for given resource.
     *
     * @param  string $resourceName
     * @param  callable $callback
     * @return Maniple_Application_ResourceContainer
     * @throws InvalidArgumentException
     */
    public function onReady($resourceName, $callback) // {{{
    {
        if (!is_callable($callback)) {
            throw new InvalidArgumentException('Invalid callable provided');
        }
        $this->_readyCallbacks[$resourceName][] = $callback;
        return $this;
    } // }}}

    /**
     * Fire callback upon gived resource initialization, or fire immediately
     * if the resource is already initialized.
     *
     * @param  string $resourceName
     * @param  callable $callback
     * @return Maniple_Application_ResourceContainer
     */
    public function whenReady($resourceName, $callback) // {{{
    {
        if ($this->isReady($resourceName)) {
            call_user_func($callback, $this->_resources[$resourceName], $this);
        } else {
            $this->onReady($resourceName, $callback);
        }
        return $this;
    } // }}}

    /**
     * @return array
     */
    protected function _prepareParams($params) // {{{
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
    } // }}}
}
