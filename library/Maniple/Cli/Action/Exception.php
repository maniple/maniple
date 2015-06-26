<?php

class Maniple_Cli_Action_Exception extends RuntimeException
{
    /**
     * @var Maniple_Cli_Action_Abstract
     */
    protected $_action;

    /**
     * @param Maniple_Cli_Action_Abstract $action
     * @param string $message
     */
    public function __construct(Maniple_Cli_Action_Abstract $action, $message)
    {
        parent::__construct($message);
        $this->_action = $action;
    }

    /**
     * @return Maniple_Cli_Action_Abstract
     */
    public function getAction()
    {
        return $this->_action;
    }
}