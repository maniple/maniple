<?php

interface Maniple_Application_ModuleBootstrapper
{
    /**
     * @param  string $module
     * @return mixed
     */
    public function bootstrapModule($module);

    /**
     * @param  string $task
     * @param  Maniple_Application_Module_Bootstrap $bootstrap
     * @return mixed
     */
    public function runTask($task, Maniple_Application_Module_Bootstrap $bootstrap);
}
