<?php

abstract class Maniple_Cli_Action_Abstract implements Maniple_Cli_Action_Interface
{
    /**
     * @var array
     */
    protected $_args;

    /**
     * @return string
     */
    public function getName()
    {
        $name = get_class($this);
        if (($pos = strrpos($name, '_')) !== false) {
            $name = substr($name, $pos + 1);
        }
        return $name;
    }

    /**
     * @param array $args
     */
    public function setArgs(array $args)
    {
        $this->_args = $args;
    }

    /**
     * @param array $args OPTIONAL
     * @return mixed
     */
    public function run(array $args = null)
    {
        if ($args === null) {
            $args = $this->_args;
        }
        return call_user_func_array(array($this, '_run'), $args);
    }

    abstract protected function _run();
}