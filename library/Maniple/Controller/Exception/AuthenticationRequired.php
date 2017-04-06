<?php

class Maniple_Controller_Exception_AuthenticationRequired extends Maniple_Controller_Exception
{
    /**
     * @var string
     */
    protected $_continueUrl;

    public function __construct($message = 'Authentication Required', $continueUrl = null)
    {
        parent::__construct($message, 401);
        $this->setContinueUrl($continueUrl);
    }

    /**
     * @return string
     */
    public function getContinueUrl()
    {
        return $this->_continueUrl;
    }

    /**
     * @param $continueUrl
     * @return Maniple_Controller_Exception_AuthenticationRequired
     */
    public function setContinueUrl($continueUrl)
    {
        $this->_continueUrl = $continueUrl;
        return $this;
    }
}
