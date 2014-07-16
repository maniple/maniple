<?php

abstract class Maniple_Application_Resource_ResourceAbstract
    extends Zend_Application_Resource_ResourceAbstract
{
    /**
     * Get a resource from bootstrap, initialize it if necessary.
     *
     * @param  string $name
     * @return mixed
     */
    protected function _getBootstrapResource($name) // {{{
    {
        $bootstrap = $this->getBootstrap();

        if ($bootstrap->hasResource($name)) {
            $resource = $bootstrap->getResource($name);
        } elseif ($bootstrap->hasPluginResource($name) || method_exists($bootstrap, '_init' . $name)) {
            $bootstrap->bootstrap($name);
            $resource = $bootstrap->getResource($name);
        } else {
            $resource = null;
        }

        return $resource;
    } // }}}
}
