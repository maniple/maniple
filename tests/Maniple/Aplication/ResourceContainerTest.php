<?php

require_once 'Maniple/Application/ResourceContainer.php';

class Maniple_Application_ResourceContainerTest
    extends PHPUnit_Framework_TestCase
{
    public function testHasResourceAfterAddResource()
    {
        $container = new Maniple_Application_ResourceContainer();
        $container->addResource('obj', new stdClass);
        $this->assertTrue($container->hasResource('obj'));
    }

    /**
     * @expectedException Exception
     */
    public function testThrowsOnExistingResource()
    {
        $container = new Maniple_Application_ResourceContainer();
        $container->addResource('obj', new stdClass);
        $container->addResource('obj', new stdClass);
    }

    public function testAddedObjectIsReady()
    {
        $container = new Maniple_Application_ResourceContainer();
        $container->addResource('obj', new stdClass);
        $this->assertTrue($container->isReady('obj'));
    }

    public function testAddedAsDescriptionIsNotReady()
    {
        $container = new Maniple_Application_ResourceContainer();
        $container->addResource('obj', array('class' => 'stdClass'));
        $this->assertFalse($container->isReady('obj'));
    }

    public function testAddedAsAliasIsNotReady()
    {
        $container = new Maniple_Application_ResourceContainer();
        $container->addResource('obj', 'resource:obj_alias');
        $this->assertFalse($container->isReady('obj'));
    }

    public function testReadyCallback()
    {
        $container = new Maniple_Application_ResourceContainer();   

        $this->_objReadyCalled = false;

        $container->onReady('obj', array($this, 'objReadyCallback'));
        $container->addResource('obj', array('class' => 'stdClass'));
        $container->getResource('obj');

        $this->assertTrue($this->_objReadyCalled);
    }

    public function testWhenReadyCallback()
    {
        $container = new Maniple_Application_ResourceContainer();   

        $this->_objReadyCalled = false;

        $container->addResource('obj', new stdClass);
        $container->whenReady('obj', array($this, 'objReadyCallback'));

        $this->assertTrue($this->_objReadyCalled);
    }

    public function testWhenReadyCallbackFromDefinition()
    {
        $container = new Maniple_Application_ResourceContainer();   

        $this->_objReadyCalled = false;

        $container->addResource('obj', 'stdClass');
        $container->whenReady('obj', array($this, 'objReadyCallback'));

        $this->assertFalse($this->_objReadyCalled);
        $container->getResource('obj');
        $this->assertTrue($this->_objReadyCalled);
    }

    public function objReadyCallback($obj)
    {
        $this->_objReadyCalled = true;
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testInvalidReadyCallback()
    {
        $container = new Maniple_Application_ResourceContainer();
        $container->onReady('obj', 0xDEADBEEF);
    }

    /**
     * @expectedException Exception
     */
    public function testRetrieveUnregisteredResource()
    {
        $container = new Maniple_Application_ResourceContainer();
        $container->getResource('obj');
    }

    public function testWhatever()
    {
        $container = new Maniple_Application_ResourceContainer();
        $container->addResource('obj', 'stdClass');
        $this->assertFalse($container->isReady('obj'));

        

        $this->assertInstanceOf('stdClass', $container->getResource('obj'));


        $this->assertTrue($container->isReady('obj'));

        $container->removeResource('obj');
        $this->assertFalse($container->hasResource('obj'));
        $this->assertFalse($container->isReady('obj'));
    }

    public function testGetProxy()
    {
        $obj = new stdClass;
        $container = new Maniple_Application_ResourceContainer();
        $container->addResource('obj', $obj);
        $this->assertTrue($container->obj === $obj);
    }

    public function testSetProxy()
    {
        $obj = new stdClass;
        $container = new Maniple_Application_ResourceContainer();
        $container->obj = $obj;
        $this->assertTrue($container->getResource('obj') === $obj);
    }

    public function testAddMultipleResources()
    {
        $resources = array(
            'obj1' => new stdClass,
            'obj2' => new stdClass,
            'obj3' => new stdClass,
        );
        $container = new Maniple_Application_ResourceContainer();
        $container->addResources($resources);

        foreach ($resources as $name => $resource) {
            $this->assertTrue($container->getResource($name) === $resource);
        }
    }

    public function testAddMultipleResourcesInContructor()
    {
        $resources = array(
            'obj1' => new stdClass,
            'obj2' => new stdClass,
            'obj3' => new stdClass,
        );
        $container = new Maniple_Application_ResourceContainer($resources);

        foreach ($resources as $name => $resource) {
            $this->assertTrue($container->getResource($name) === $resource);
        }
    }

    public function testAlias()
    {
        $obj = new stdClass;

        $container = new Maniple_Application_ResourceContainer();
        $container->addResource('obj', $obj);
        $container->addResource('obj_alias', 'resource:obj');
        $container->addResource('obj_alias2', 'resource:obj_alias');

        $this->assertTrue($container->isReady('obj'));

        $this->assertTrue($container->hasResource('obj_alias'));
        $this->assertTrue($container->isReady('obj_alias'));

        $this->assertTrue($container->hasResource('obj_alias2'));
        $this->assertTrue($container->isReady('obj_alias2')); // alias inherits isReady flag
        $this->assertTrue($container->getResource('obj_alias2') === $obj);
    }
}
