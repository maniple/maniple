<?php

class Maniple_Controller_Exception_AuthenticationRequired extends Maniple_Controller_Exception
{
    /**
     * @var string
     */
    protected $_continueUrl;

    /**
     * @param string|Zend_Controller_Request_Http $message
     * @param string $continueUrl
     */
    public function __construct($message = null, $continueUrl = null, Exception $previous = null)
    {
        if ($message instanceof Zend_Controller_Request_Http) {
            $continueUrl = $message->getRequestUri();
            $message = null;
        }
        if ($message === null) {
            $message = 'Authentication Required';
        }
        parent::__construct($message, 401, $previous);
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
     * @param string $continueUrl
     * @return $this
     */
    public function setContinueUrl($continueUrl)
    {
        $this->_continueUrl = $continueUrl;
        return $this;
    }
}
