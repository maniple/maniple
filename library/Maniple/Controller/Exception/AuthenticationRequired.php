<?php

class Maniple_Controller_Exception_AuthenticationRequired extends RuntimeException
{
    protected $_continueUrl;

    public function __construct($message = 'Authentication Required', $continue = null)
    {
        parent::__construct($message, 401);
        $this->_continueUrl = $continue;
    }

    public function getContinueUrl()
    {
        return $this->_continueUrl;
    }
}
