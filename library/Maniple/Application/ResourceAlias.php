<?php

/**
 * @internal
 */
class Maniple_Application_ResourceAlias
{
    /**
     * @var string
     */
    protected $_target;

    /**
     * @param string $target
     */
    public function __construct($target)
    {
        $this->_target = (string) $target;
    }

    /**
     * @return string
     */
    public function getTarget()
    {
        return $this->_target;
    }
}
