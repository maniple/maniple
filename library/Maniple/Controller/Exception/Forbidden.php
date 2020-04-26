<?php

class Maniple_Controller_Exception_Forbidden extends Maniple_Controller_Exception
{
    public function __construct($message = 'Forbidden', $code = 403, Exception $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
