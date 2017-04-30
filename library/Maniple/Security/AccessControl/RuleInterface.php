<?php

interface Maniple_Security_AccessControl_RuleInterface
{
    /**
     * @param mixed $resource
     * @param mixed $permission
     * @return boolean
     */
    public function supports($resource, $permission);

    /**
     * @param $securityContext
     * @param mixed $resource
     * @param string $permission
     * @return boolean
     */
    public function isAllowed(Maniple_Security_ContextAbstract $securityContext, $resource, $permission);
}
