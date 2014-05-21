<?php

interface Maniple_Security_ContextInterface
{
    /**
     * @return Maniple_Security_UserInterface
     */
    public function getUser();

    /**
     * @return bool
     */
    public function isAuthenticated();

    /**
     * @param  mixed $permission
     * @return bool
     */
    public function isAllowed($permission);

    /**
     * @return bool
     */
    public function isSuperUser();
}
