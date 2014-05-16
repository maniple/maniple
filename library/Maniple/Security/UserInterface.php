<?php

interface Maniple_Security_User
{
    /**
     * @return mixed
     */
    public function getId();

    /**
     * @return array
     */
    public function getRoles();
}
