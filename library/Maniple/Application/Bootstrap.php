<?php

/**
 * @version 2014-03-10 / 2013-12-05
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
     * Initialize resource of a given name, if it's not already initialized
     * and return the result.
     *
     * @param  null|string|array $resource OPTIONAL
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
     * @param  Zend_Application_Module_Bootstrap $module
     * @return void
     * @throws InvalidArgumentException
     */
    protected function _executeModuleRoutes($module) // {{{
    {
        $router = $this->_bootstrap('frontController')->getRouter();

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
    } // }}}

    /**
     * @param  Zend_Application_Module_Bootstrap $module
     * @return void
     * @throws InvalidArgumentException
     */
    protected function _executeModuleResources($module) // {{{
    {
        if (method_exists($module, 'getResources')) {
            foreach ($module->getResources() as $name => $resource) {
                if ($this->hasResource($name)) {
                    throw new InvalidArgumentException(sprintf(
                        "Resource '%s' is already registered", $name
                    ));
                }
                $this->_setResource($name, $resource);
            }
        }
    } // }}}

    protected function _initModules() // {{{
    {
        if (!$this->hasPluginResource('modules')) {
            return;
        }

        $modules = $this->getPluginResource('modules')->init();

        // Zend_Loader_Autoloader is initialized in the Zend_Application ctor
        // application autoloader is a simple one which only mapps _ and \\
        // to directory separators and utilized include paths.
        // Here we need autoloader for module library directory which maps
        // Module_ClassName to module/library/ClassName.php

        $moduleLibraryAutoloader = new Zend_Loader_StandardAutoloader();
        $moduleLibraryAutoloader->register(); // add to autoload stack

        // add libary dir to include path, see:
        // http://stackoverflow.com/questions/13377983/zend-framework-module-library
        foreach ($modules as $name => $module) {
            $ref = new ReflectionClass($module);
            $path = dirname($ref->getFileName()) . '/library';

            if (is_dir($path)) {
                $moduleLibraryAutoloader->registerPrefix(ucfirst($name) . '_', $path);
                // set_include_path($path . PATH_SEPARATOR . get_include_path());
            }

            // The last stage of module initialization: registers resources and
            // routes defined by modules.
            $this->_executeModuleRoutes($module);
            $this->_executeModuleResources($module);
        }

        return $modules;
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
