<?php

class Maniple_Controller_Exception_MethodNotAllowed extends Maniple_Controller_Exception
{
    public function __construct($message = 'Method Not Allowed', $code = 405, Exception $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
