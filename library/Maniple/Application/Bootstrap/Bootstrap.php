<?php

/**
 * @version 2015-03-11
 * @author xemlock
 */
class Maniple_Application_Bootstrap_Bootstrap
    extends Zefram_Application_Bootstrap_Bootstrap
{
    /**
     * Get the plugin loader for resources.
     *
     * @return Zend_Loader_PluginLoader_Interface
     */
    public function getPluginLoader() // {{{
    {
        if ($this->_pluginLoader === null) {
            parent::getPluginLoader()->addPrefixPath(
                'Maniple_Application_Resource_',
                'Maniple/Application/Resource/'
            );
        }
        return $this->_pluginLoader;
    } // }}}

    /**
     * Loads a plugin resource.
     *
     * If a 'class' option is provided, a lazy resource is instantiated
     * instead of a plugin resource. To override this behavior (e.g. when
     * 'class' is a valid option for a plugin resource), add 'plugin' option
     * with a truthy value.
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

        if (isset($options['class']) && empty($options['plugin'])) {
            $result = strtolower($resource);
            $this->_pluginResources[$result] = new Maniple_Application_Resource_LazyResource($options);
        } else {
            unset($options['plugin']);
            $result = parent::_loadPluginResource($resource, $options);
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
