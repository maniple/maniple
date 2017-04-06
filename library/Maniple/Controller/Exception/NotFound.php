<?php

class Maniple_Controller_Exception_NotFound extends Maniple_Controller_Exception
{
    public function __construct($message = 'Not Found', $code = 404, Exception $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
