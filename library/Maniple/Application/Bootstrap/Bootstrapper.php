<?php

interface Maniple_Application_Bootstrap_Bootstrapper
    extends Zend_Application_Bootstrap_Bootstrapper
{
    /**
     * Is the requested class resource registered?
     *
     * @param  string $resource
     * @return bool
     */
    public function hasClassResource($resource);
}
