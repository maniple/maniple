<?php

class Maniple_Controller_Action extends Zefram_Controller_Action
{
    public function getSecurity()
    {
        return $this->getResource('security');
    }

    /**
     * @throws Maniple_Controller_Exception_AuthenticationRequired
     */
    public function requireAuthentication()
    {
        if (!$this->getSecurity()->isAuthenticated()) {
            throw new Maniple_Controller_Exception_AuthenticationRequired(
                $this->view->translate('You need to be authenticated to perform this action'),
                $this->_request->getRequestUri()
            );
        }
    }
}
