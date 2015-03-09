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

    /**
     * Get custom data associated with this storage.
     *
     * @param  string $name
     * @return mixed
     */
    public function get($name);

    /**
     * Set custom data associated with this storage.
     *
     * @param  string $name
     * @param  mixed $value
     */
    public function set($name, $value);
}
