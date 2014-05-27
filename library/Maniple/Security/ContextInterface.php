<?php

interface Maniple_Security_ContextInterface
{
    /**
     * Retrieves currently authenticated user.
     *
     * @return Maniple_Security_UserInterface
     */
    public function getUser();

    /**
     * Is any user authenticated.
     *
     * @return bool
     */
    public function isAuthenticated();

    /**
     * Is currently authenticated user a super-user.
     *
     * @return bool
     */
    public function isSuperUser();

    /**
     * Does currently authenticated user have a given permission.
     *
     * @param  mixed $permission
     * @return bool
     */
    public function isAllowed($permission);
}
