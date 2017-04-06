<?php

class Maniple_Controller_Exception_BadRequest extends Maniple_Controller_Exception
{
    public function __construct($message = 'Bad Request', $code = 400, Exception $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
