<?php

interface Maniple_Application_ModuleBootstrapper
{
    public function bootstrapModule($name);

    public function getBootstrap();
}
