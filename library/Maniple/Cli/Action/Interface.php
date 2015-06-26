<?php

interface Maniple_Cli_Action_Interface
{
    /**
     * @return string
     */
    public function getName();

    /**
     * @return mixed
     */
    public function run();
}