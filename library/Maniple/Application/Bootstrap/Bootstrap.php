<?php

/**
 * @version 2014-07-20
 * @author xemlock
 */
class Maniple_Application_Bootstrap_Bootstrap
    extends Zend_Application_Bootstrap_Bootstrap
    implements Maniple_Application_Bootstrap_Bootstrapper
{
    /**
     * Default resource container class.
     *
     * @var string
     */
    protected $_containerClass = 'Maniple_Application_ResourceContainer';

    /**
     * Configure this instance.
     *
     * @param  array $options
     * @return Maniple_Application_Bootstrap_Bootstrap
     */
    public function setOptions(array $options) // {{{
    {
        parent::setOptions($options);

        if (isset($this->_options['bootstrap']['containerClass'])) {
            $this->_containerClass = $this->_options['bootstrap']['containerClass'];
        }

        return $this;
    } // }}}

    /**
     * Get the plugin loader for resources.
     *
     * @return Zend_Loader_PluginLoader_Interface
     */
    public function getPluginLoader() // {{{
    {
        if ($this->_pluginLoader === null) {
            $prefixPaths = array(
                'Zefram_Application_Resource_'  => 'Zefram/Application/Resource/',
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
     * If no container is present a new container is created. Container class
     * can be configured via 'containerClass' option under 'bootstrap' section.
     *
     * @return object
     */
    public function getContainer() // {{{
    {
        if (null === $this->_container) {
            $containerClass = $this->_containerClass;
            $container = new $containerClass;
            $this->setContainer($container);
        }
        return $this->_container;
    } // }}}

    /**
     * Save given resource using a custom name without involving _init method
     * or plugin mechanism.
     *
     * @param  string $name
     * @param  mixed $value
     * @return Maniple_Application_Bootstrap_Bootstrap
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
     * Is the requested class resource registered?
     *
     * @param  string $resource
     * @return bool
     */
    public function hasClassResource($resource) // {{{
    {
        return method_exists($this, '_init' . $resource);
    } // }}}

    /**
     * Loads a plugin resource.
     *
     * If the options contain a truthy 'lazyResource' value, no plugin lookup
     * is performed and a lazy resource is instantiated instead.
     *
     * Lazy resources, in order to work as intended, must be supported by
     * the resource container the bootstrap operates on.
     *
     * @param  string $resource
     * @param  array|object|null $options
     * @return string|false
     */
    protected function _loadPluginResource($resource, $options = null) // {{{
    {
        if (is_object($options) && method_exists($options, 'toArray')) {
            $options = $options->toArray();
        }
        $options = (array) $options;

        if (empty($options['lazyResource'])) {
            $result = parent::_loadPluginResource($resource, $options);
        } else {
            $result = strtolower($resource);
            $this->_pluginResources[$result] = new Maniple_Application_Resource_LazyResource($options);
        }

        return $result;
    } // }}}

    /**
     * Bootstrap module.
     *
     * @deprecated
     * @param  string $module
     * @return Maniple_Application_Bootstrap_Bootstrap
     */
    public function bootstrapModule($module) // {{{
    {
        $this->getPluginResource('modules')->bootstrapModule($module);
        return $this;
    } // }}}
}
