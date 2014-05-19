<?php

/**
 * @version 2014-05-19
 * @author xemlock
 */
class Maniple_Application_Bootstrap extends Zend_Application_Bootstrap_Bootstrap
    implements ArrayAccess
{
    /**
     * Get the plugin loader for resources
     *
     * @return Zend_Loader_PluginLoader_Interface
     */
    public function getPluginLoader() // {{{
    {
        if ($this->_pluginLoader === null) {
            $prefixPaths = array(
                'Zefram_Application_Resource_' => 'Zefram/Application/Resource/',
                'Maniple_Application_Resource_' => 'Maniple/Application/Resource/',
            );
            foreach ($prefixPaths as $prefix => $path) {
                parent::getPluginLoader()->addPrefixPath($prefix, $path);
            }
        }
        return $this->_pluginLoader;
    } // }}}

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
    public function setResource($name, $value) // {{{
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
     * @deprecated
     */
    protected function _setResource($name, $value)
    {
        return $this->setResource($name, $value);
    }

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
     * Proxy to {@see setResource()}.
     *
     * @param  string $offset
     * @param  mixed $value
     * @return void
     */
    public function offsetSet($offset, $value) // {{{
    {
        $this->setResource($offset, $value);
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
