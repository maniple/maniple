<?php

/**
 * @version 2013-12-05
 * @author xemlock
 */
class Maniple_Application_Bootstrap extends Zend_Application_Bootstrap_Bootstrap
    implements ArrayAccess
{
    /**
     * Retrieve resource container.
     *
     * @return object
     */
    public function getContainer() // {{{
    {
        if (null === $this->_container) {
            $serviceLocator = new Maniple_Application_ServiceLocator(array(
                'bootstrap' => $this,
            ));
            $this->setContainer($serviceLocator);
        }
        return $this->_container;
    } // }}}

    /**
     * Save given resource using a custom name without involving _init method
     * or plugin mechanism.
     *
     * @param  string $name
     * @param  mixed $value
     * @return Maniple_Application_Bootstrap
     */
    protected function _setResource($name, $value) // {{{
    {
        $resource = strtolower($name);

        if ($this->hasResource($resource)) {
            throw new Zend_Application_Bootstrap_Exception(sprintf(
                "Resource '%s' already exists", $resource
            ));
        }

        $this->getContainer()->{$resource} = $value;
        return $this;
    } // }}}

    /**
     * Initialize resource of a given name, if it's not already initialized
     * and return the results.
     *
     * @param  string $resource OPTIONAL
     * @return mixed
     */
    protected function _bootstrap($resource = null) // {{{
    {
        parent::_bootstrap($resource);

        if (null !== $resource && $this->hasResource($resource)) {
            return $this->getResource($resource);
        }
    } // }}}

    /**
     * Load routes defined by loaded modules.
     *
     * @return void
     * @throws InvalidArgumentException
     */
    protected function _initRoutes() // {{{
    {
        if (!method_exists($this, '_initModules') && !$this->hasPluginResource('modules')) {
            // no modules resource available
            return;
        }

        $modules = $this->_bootstrap('modules');
        $router = $this->_bootstrap('frontController')->getRouter();

        foreach ($modules as $module) {
            if (method_exists($module, 'getRoutes')) {
                $routes = $module->getRoutes();

                if (is_array($routes)) {
                    $routes = new Zend_Config($routes);
                }

                if (!$routes instanceof Zend_Config) {
                    throw new InvalidArgumentException('Route config must be an instance of Zend_Config');
                }

                $router->addConfig($routes);
            }
        }
    } // }}}

    /**
     * Proxy to {@see getResource()}.
     *
     * @param  string $offset
     * @return mixed
     */
    public function offsetGet($offset) // {{{
    {
        return $this->getResource($offset);
    } // }}}

    /**
     * Proxy to {@see _setResource()}.
     *
     * @param  string $offset
     * @param  mixed $value
     * @return void
     */
    public function offsetSet($offset, $value) // {{{
    {
        $this->_setResource($offset, $value);
    } // }}}

    /**
     * Does resource of given name exist.
     *
     * @param  string $offset
     * @return boolean
     */
    public function offsetExists($offset) // {{{
    {
        return isset($this->getContainer()->{$offset});
    } // }}}

    /**
     * Removes resource from container.
     *
     * @param  string $offset
     * @return void
     */
    public function offsetUnset($offset) // {{{
    {
        // TODO does it initialize resource before removing it? Check!
        unset($this->getContainer()->{$offset});
    } // }}}
}
