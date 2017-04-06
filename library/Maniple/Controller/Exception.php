<?php

class Maniple_Controller_Exception extends Zend_Exception
{
    public function __construct($message = 'Application Error', $code = 500, Exception $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
