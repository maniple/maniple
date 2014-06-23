<?php

interface Maniple_Security_UserStorageInterface
{
    /**
     * @return Maniple_Security_UserInterface
     */
    public function getUser();

    /**
     * @param  Maniple_Security_UserInterface $user
     * @return mixed
     */
    public function setUser(Maniple_Security_UserInterface $user);

    /**
     * @return mixed
     */
    public function clearUser();
}
