<?php

class Maniple_Controller_Exception_NotAllowed extends RuntimeException
{
    public function __construct($message = 'Not Allowed')
    {
        parent::__construct($message, 403);
    }
}
