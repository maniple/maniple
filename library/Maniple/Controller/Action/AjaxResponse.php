<?php

class Maniple_Controller_Action_AjaxResponse extends Maniple_Controller_Action_AjaxResponse_Abstract
{
    const STATUS_SUCCESS = 'success';
    const STATUS_FAIL    = 'fail';
    const STATUS_ERROR   = 'error';

    protected $_contentType = 'application/json';

    protected $_status = self::STATUS_SUCCESS;
    protected $_message;
    protected $_data;

    public function setMessage($message)
    {
        $this->_message = (string) $message;
        return $this;
    }

    public function getMessage()
    {
        return $this->_message;
    }

    public function setData($data)
    {
        $this->_data = $data;
        return $this;
    }

    public function getData()
    {
        return $this->_data;
    }

    public function setStatus($status)
    {
        $this->_status = (string) $status;
        return $this;
    }

    public function getStatus()
    {
        return $this->_status;
    }

    public function setSuccess($message = null)
    {
        $this->_status = self::STATUS_SUCCESS;
        if ($message) {
            $this->_message = (string) $message;
        }
        return $this;
    }

    public function setFail($message, $code = null)
    {
        $this->_status = self::STATUS_FAIL;
        $this->_message = (string) $message;
        return $this;
    }

    public function setError($message, $code = null)
    {
        $this->_status = self::STATUS_ERROR;
        $this->_message = (string) $message;
        return $this;
    }

    public function getBody()
    {
        $response = array('status' => $this->_status);
        if ($this->_message) {
            $response['message'] = $this->_message;
        }
        if ($this->_data) {
            $response['data'] = $this->_data;
        }
        return Zefram_Json::encode($response);
    }
}
