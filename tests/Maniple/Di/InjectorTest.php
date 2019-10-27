<?php

class Maniple_Di_InjectorTest extends PHPUnit_Framework_TestCase
{
    public function testInjectAnnotationWithName()
    {
        $container = new Maniple_Di_Container();
        $container->addResource('A', new stdClass);
        $container->addResource('B', array(
            'class' => Maniple_Di_InjectorTest_B::className,
        ));

        $injector = new Maniple_Di_Injector($container);
        $container->setInjector($injector);

        $this->assertInstanceOf(Maniple_Di_InjectorTest_B::className, $container->getResource('B'));
        $this->assertSame($container->getResource('A'), $container->getResource('B')->getA());
    }

    public function testInjectAnnotationWithType()
    {
        $container = new Maniple_Di_Container();
        $container->addResource('A', new stdClass);
        $container->addResource(Maniple_Di_InjectorTest_B::className, array(
            'class' => Maniple_Di_InjectorTest_B::className,
        ));
        $container->addResource('C', array(
            'class' => Maniple_Di_InjectorTest_C::className,
        ));

        $injector = new Maniple_Di_Injector($container);
        $container->setInjector($injector);

        $this->assertInstanceOf(
            Maniple_Di_InjectorTest_B::className,
            $container->getResource(Maniple_Di_InjectorTest_B::className)
        );

        $this->assertInstanceOf(
            Maniple_Di_InjectorTest_C::className,
            $container->getResource('C')
        );

        $this->assertSame(
            $container->getResource(Maniple_Di_InjectorTest_B::className),
            $container->getResource('C')->getB()
        );
    }

    public function testNewInstance()
    {
        $container = new Maniple_Di_Container();
        $container->addResource('A', new stdClass);

        $injector = $container->getInjector();

        /** @var Maniple_Di_InjectorTest_B $instance */
        $instance = $injector->newInstance(Maniple_Di_InjectorTest_B::className);

        $this->assertInstanceOf(Maniple_Di_InjectorTest_B::className, $instance);
        $this->assertSame($container->getResource('A'), $instance->getA());
    }

    public function testNullableDependency()
    {
        $container = new Maniple_Di_Container();

        $injector = $container->getInjector();

        /** @var Maniple_Di_InjectorTest_HasNullableB $instance */
        $instance = $injector->newInstance(Maniple_Di_InjectorTest_HasNullableB::className);

        $this->assertInstanceOf(Maniple_Di_InjectorTest_HasNullableB::className, $instance);
        $this->assertNull($instance->b);
    }
}

class Maniple_Di_InjectorTest_B
{
    const className = __CLASS__;

    /**
     * @Inject('A')
     */
    protected $_a;

    public function getA()
    {
        return $this->_a;
    }
}

class Maniple_Di_InjectorTest_C
{
    const className = __CLASS__;

    /**
     * @Inject
     * @var Maniple_Di_InjectorTest_B
     */
    protected $_b;

    public function getB()
    {
        return $this->_b;
    }
}

class Maniple_Di_InjectorTest_HasNullableB
{
    const className = __CLASS__;

    /**
     * @Inject('b')
     * @var Maniple_Di_InjectorTest_B|null
     */
    public $b;
}
