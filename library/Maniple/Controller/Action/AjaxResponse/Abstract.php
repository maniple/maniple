<?php

/**
 * @version 2013-07-15
 */
abstract class Maniple_Controller_Action_AjaxResponse_Abstract
{
    protected $_code = 200;

    protected $_contentType = 'text/html';

    abstract public function setSuccess($message = null);

    abstract public function setFail($message, $code = null);

    abstract public function setError($message, $code = null);

    abstract public function setData($data);

    abstract public function getBody();

    public function setCode($code)
    {
        $this->_code = $code;
        return $this;
    }

    public function getCode()
    {
        return $this->_code;
    }

    public function setContentType($contentType)
    {
        $this->_contentType = $contentType;
        return $this;
    }

    public function getContentType()
    {
        return $this->_contentType;
    }

    public function send()
    {
        $response = Zend_Controller_Front::getInstance()->getResponse();
        $response->setHttpResponseCode($this->_code);
        $response->setHeader('Content-Type', $this->getContentType());
        $response->setBody($this->getBody());
        $response->sendHeaders();
        $response->sendResponse();
        return $this;
    }

    public function sendAndExit()
    {
        $this->send();
        if (class_exists('Zend_Session', false) && Zend_Session::isStarted()) {
            Zend_Session::writeClose();
        } elseif (isset($_SESSION)) {
            session_write_close();
        }
        exit;
    }
}
