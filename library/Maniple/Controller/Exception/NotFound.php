<?php

class Maniple_Controller_Exception_NotFound extends RuntimeException
{
    public function __construct($message = 'Not Found')
    {
        parent::__construct($message, 404);
    }
}
