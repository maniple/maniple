<?php

/**
 * Interface for module task.
 *
 * Module tasks are common routines executed as the last stage of module
 * bootstrapping.
 *
 * @deprecated
 */
interface Maniple_Application_Module_Task_TaskInterface
{
    /**
     * Task implementation.
     *
     * @param  Maniple_Application_Module_Bootstrap $bootstrap
     */
    public function run(Maniple_Application_Module_Bootstrap $bootstrap);
}
