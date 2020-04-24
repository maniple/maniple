<?php

/**
 * @deprecated
 */
abstract class Maniple_Application_Resource_ResourceAbstract
    extends Zend_Application_Resource_ResourceAbstract
{
    /**
     * Retrieve resource option(s).
     *
     * @param  string $name OPTIONAL
     * @return mixed
     */
    public function getOptions($name = null) // {{{
    {
        if (null === $name) {
            return $this->_options;
        }
        return isset($this->_options[$name]) ? $this->_options[$name] : null;
    } // }}}

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
