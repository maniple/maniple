<?php

class Maniple_Controller_Exception_BadRequest extends RuntimeException
{
    public function __construct($message = 'Bad Request')
    {
        parent::__construct($message, 400);
    }
}
