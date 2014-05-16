<?php

interface Maniple_Security_UserInterface
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
