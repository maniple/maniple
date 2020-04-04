<?php

class Maniple_Application_Module_BootstrapTest extends PHPUnit_Framework_TestCase
{
    protected $_application;

    protected function setUp()
    {
        $this->_application = new Zefram_Application('test');
    }

    public function testBootstrapInContainer()
    {
        $module = new Maniple_Application_Module_BootstrapTest_Bootstrap($this->_application);
        $module->bootstrap();

        $this->assertSame(
            $this->_application->getBootstrap()->getContainer()->{get_class($module)},
            $module
        );
    }

}

class Maniple_Application_Module_BootstrapTest_Bootstrap
    extends Maniple_Application_Module_Bootstrap
{
    public function getModuleDependencies()
    {
        return array();
    }
}
