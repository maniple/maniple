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
                realpath(dirname(__FILE__) . '/../Resource/')
            );
        }
        return $this->_pluginLoader;
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


    protected function _bootstrap($resource = null)
    {
        if ($resource === null && $this->hasPluginResource('modules')) {
            $modules = $this->getPluginResource('modules');
            $modules->preInit(); // to powinno zaktualizować config
        }
        return parent::_bootstrap($resource);
    }
}
