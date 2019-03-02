<?php

/**
 * @version 2015-03-11
 * @author xemlock
 * @deprecated
 */
class Maniple_Application_Bootstrap_Bootstrap
    extends Zefram_Application_Bootstrap_Bootstrap
{
    protected $_containerClass = 'Maniple_Application_ResourceContainer';

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
}
