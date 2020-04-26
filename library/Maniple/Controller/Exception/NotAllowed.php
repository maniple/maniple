<?php

/**
 * @deprecated Use {@link Maniple_Controller_Exception_Forbidden}
 */
class Maniple_Controller_Exception_NotAllowed extends Maniple_Controller_Exception
{
    public function __construct($message = 'Not Allowed', $code = 403, Exception $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
