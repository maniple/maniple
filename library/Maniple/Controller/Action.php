<?php

class Maniple_Controller_Action extends Zefram_Controller_Action
{
    /**
     * @return Zend_Application_Bootstrap_BootstrapAbstract
     * @throws Exception
     */
    public function getBootstrap() // {{{
    {
        $bootstrap = $this->getFrontController()->getParam('bootstrap');
        if (!$bootstrap instanceof Zend_Application_Bootstrap_BootstrapAbstract) {
            throw new Exception('Bootstrap is not available');
        }
        return $bootstrap;
    } // }}}

    /**
     * @param  string $name
     * @return mixed
     */
    public function getResource($name) // {{{
    {
        return $this->getBootstrap()->getResource($name);
    } // }}}
}
