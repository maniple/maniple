<?php

class Maniple_Application_Bootstrap extends Zend_Application_Bootstrap_Bootstrap
{
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
}
