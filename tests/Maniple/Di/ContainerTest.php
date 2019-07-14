<?php

class Maniple_Di_ContainerTest extends PHPUnit_Framework_TestCase
{
    protected $_container;

    public function getContainer()
    {
        if ($this->_container === null) {
            $this->_container = new Maniple_Di_Container();
        }
        return $this->_container;
    }

    public function testResourceCallback()
    {
        $container = $this->getContainer();
        $callback = Maniple_Di_ContainerTest_Res::className . '::factory';

        $container->addResourceCallback('pokemon', $callback, array('Gyarados'));
        $resource = $container->getResource('pokemon');

        $this->assertInstanceOf(Maniple_Di_ContainerTest_Res::className, $resource);
        $this->assertEquals('Gyarados', $resource->getName());

        $callback = new Zefram_Stdlib_CallbackHandler($callback, array(), array('Lugia'));
        $container->addResourceCallback('pokemon2', $callback);
        $resource = $container->getResource('pokemon2');

        $this->assertInstanceOf(Maniple_Di_ContainerTest_Res::className, $resource);
        $this->assertEquals('Lugia', $resource->getName());
    }

    /**
     * @expectedException Exception
     */
    public function testThrowsOnExistingResource()
    {
        $container = new Maniple_Di_Container();
        $container->addResource('obj', new stdClass);
        $container->addResource('obj', new stdClass);
    }

    /**
     * @expectedException Exception
     */
    public function testRetrieveUnregisteredResource()
    {
        $container = new Maniple_Di_Container();
        $container->getResource('obj');
    }

    public function testWhatever()
    {
        $container = new Maniple_Di_Container();
        $container->addResource('obj', array('class' => 'stdClass'));

        $this->assertInstanceOf('stdClass', $container->getResource('obj'));

        $container->removeResource('obj');
        $this->assertFalse($container->hasResource('obj'));
    }

    public function testGetProxy()
    {
        $obj = new stdClass;
        $container = new Maniple_Di_Container();
        $container->addResource('obj', $obj);
        $this->assertTrue($container->obj === $obj);
    }

    public function testSetProxy()
    {
        $obj = new stdClass;
        $container = new Maniple_Di_Container();
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
        $container = new Maniple_Di_Container();
        $container->addResources($resources);

        foreach ($resources as $name => $resource) {
            $this->assertTrue($container->getResource($name) === $resource);
        }
    }

    public function testAddResourceNameOnly()
    {
        $container = new Maniple_Di_Container();
        $container->addResource(Maniple_Di_ContainerTest_A::className);

        $this->assertInstanceOf(
            Maniple_Di_ContainerTest_A::className,
            $container->getResource(Maniple_Di_ContainerTest_A::className)
        );
    }

    public function testAddResourcesNameOnly()
    {
        $container = new Maniple_Di_Container();
        $container->addResources(array(
            Maniple_Di_ContainerTest_A::className,
        ));

        $this->assertInstanceOf(
            Maniple_Di_ContainerTest_A::className,
            $container->getResource(Maniple_Di_ContainerTest_A::className)
        );
    }

    public function testAddMultipleResourcesInContructor()
    {
        $resources = array(
            'obj1' => new stdClass,
            'obj2' => new stdClass,
            'obj3' => new stdClass,
        );
        $container = new Maniple_Di_Container($resources);

        foreach ($resources as $name => $resource) {
            $this->assertTrue($container->getResource($name) === $resource);
        }
    }

    public function testAlias()
    {
        $obj = new stdClass;

        $container = new Maniple_Di_Container();
        $container->addResource('obj', $obj);
        $container->addResource('obj_alias', 'resource:obj');
        $container->addResource('obj_alias2', 'resource:obj_alias');

        $this->assertTrue($container->hasResource('obj_alias'));
        $this->assertTrue($container->hasResource('obj_alias2'));
        $this->assertTrue($container->getResource('obj_alias2') === $obj);
    }

    public function testMultiple()
    {
        $container = new Maniple_Di_Container();
        $container->addResource('A', array(
            'class' => Maniple_Di_ContainerTest_A::className,
            'multiple' => true,
        ));

        $a1 = $container->getResource('A');
        $a2 = $container->getResource('A');

        $this->assertInstanceOf(Maniple_Di_ContainerTest_A::className, $a1);
        $this->assertInstanceOf(Maniple_Di_ContainerTest_A::className, $a2);
        $this->assertFalse($a1 === $a2);
    }

    public function testMultipleCallback()
    {
        $container = new Maniple_Di_Container();
        $container->addResource('A', array(
            'callback' => Maniple_Di_ContainerTest_A::className . '::factory',
            'multiple' => true,
        ));

        $a1 = $container->getResource('A');
        $a2 = $container->getResource('A');

        $this->assertInstanceOf(Maniple_Di_ContainerTest_A::className, $a1);
        $this->assertInstanceOf(Maniple_Di_ContainerTest_A::className, $a2);
        $this->assertFalse($a1 === $a2);
    }

    public function testMultipleAlias()
    {
        $container = new Maniple_Di_Container();
        $container->addResource('A', array(
            'class' => Maniple_Di_ContainerTest_A::className,
            'multiple' => true,
        ));
        $container->addResource('AA', 'resource:A');

        $a1 = $container->getResource('AA');
        $a2 = $container->getResource('AA');

        $this->assertInstanceOf(Maniple_Di_ContainerTest_A::className, $a1);
        $this->assertInstanceOf(Maniple_Di_ContainerTest_A::className, $a2);
        $this->assertFalse($a1 === $a2);
    }
}

class Maniple_Di_ContainerTest_Res
{
    const className = __CLASS__;

    public function __construct($name)
    {
        $this->_name = $name;
    }

    public function getName()
    {
        return $this->_name;
    }

    public static function factory($name)
    {
        return new self($name);
    }
}

class Maniple_Di_ContainerTest_A
{
    const className = __CLASS__;

    public static function factory()
    {
        return new self();
    }
}
