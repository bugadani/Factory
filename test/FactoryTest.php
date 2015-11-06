<?php

namespace Factory\Test;

use Factory\Factory;

class FactoryTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \Factory\FactoryInterface
     */
    private $factory;

    public function setUp()
    {
        $this->factory = new Factory();
    }

    public function testSimpleLoading()
    {
        $object = $this->factory->get('stdClass');

        $this->assertInstanceOf('stdClass', $object);
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testNoStringParameters()
    {
        $this->factory->get(new \stdClass());
    }

    /**
     * @expectedException \Factory\Exceptions\InstantiationException
     */
    public function testNoClassParameters()
    {
        $this->factory->get("not a class name");
    }

    public function testLoadingWithParametersInjection()
    {
        $object = $this->factory->get('Factory\Test\Mocks\TestClass', ['armadillo', 'banana']);

        $this->assertEquals('armadillo', $object->a);
        $this->assertEquals('banana', $object->b);
    }

    /**
     * @expectedException \Factory\Exceptions\InstantiationException
     */
    public function testLoadingWithTooFewParameters()
    {
        $this->factory->get('Factory\Test\Mocks\TestClass', ['armadillo']);
    }

    /**
     * @expectedException \Factory\Exceptions\InstantiationException
     */
    public function testLoadingWithMultipleMissingParameters()
    {
        $this->factory->get('Factory\Test\Mocks\TestClass');
    }

    public function testLoadingWithNamedParametersInjection()
    {
        $object = $this->factory->get('Factory\Test\Mocks\TestClass', ['b' => 'banana', 'a' => 'armadillo']);

        $this->assertEquals('armadillo', $object->a);
        $this->assertEquals('banana', $object->b);
    }

    public function testLoadingWithDefaultParameters()
    {
        $object = $this->factory->get('Factory\Test\Mocks\TestClassWithDefaultParameter', ['armadillo']);

        $this->assertEquals('armadillo', $object->a);
        $this->assertEquals('foobar', $object->b);
    }

    /**
     * @expectedException \Factory\Exceptions\InstantiationException
     */
    public function testLoadingWithMissingNamedParameters()
    {
        $this->factory->get('Factory\Test\Mocks\TestClass', ['b' => 'banana']);
    }

    public function testParameterDefinition()
    {
        $this->factory->setParameters('Factory\Test\Mocks\TestClass', ['armadillo', 'banana']);
        $object = $this->factory->get('Factory\Test\Mocks\TestClass');

        $this->assertEquals('armadillo', $object->a);
        $this->assertEquals('banana', $object->b);
    }

    public function testNamedParameterDefinition()
    {
        $this->factory->setParameters('Factory\Test\Mocks\TestClass', ['b' => 'banana', 'a' => 'armadillo']);
        $object = $this->factory->get('Factory\Test\Mocks\TestClass');

        $this->assertEquals('armadillo', $object->a);
        $this->assertEquals('banana', $object->b);
    }

    public function testThatInstancesAreStored()
    {
        $objectA = $this->factory->get('stdClass');
        $objectB = $this->factory->get('stdClass');

        $this->assertSame($objectA, $objectB);

        $this->factory->setParameters('Factory\Test\Mocks\TestClass', ['b' => 'banana', 'a' => 'armadillo']);
        $objectA = $this->factory->get('Factory\Test\Mocks\TestClass');
        $objectB = $this->factory->get('Factory\Test\Mocks\TestClass');

        $this->assertSame($objectA, $objectB);
    }

    public function testThatObjectDependenciesAreInjected()
    {
        $object = $this->factory->get('Factory\Test\Mocks\TestClassWithObjectParameter');

        $this->assertInstanceOf('stdClass', $object->class);
    }

    public function testThatStoredObjectsAreInjected()
    {
        $std    = $this->factory->get('stdClass');
        $object = $this->factory->get('Factory\Test\Mocks\TestClassWithObjectParameter');

        $this->assertSame($std, $object->class);
    }

    public function testThatDefaultsCanBeOverridden()
    {
        $std    = $this->factory->get('stdClass');
        $object = $this->factory->get('Factory\Test\Mocks\TestClassWithObjectParameter', [new \stdClass()]);
        $this->assertNotSame($std, $object->class);

        $object = $this->factory->get('Factory\Test\Mocks\TestClassWithDefaultParameter', ['a' => 'armadillo', 'b' => 'baz']);
        $this->assertEquals('baz', $object->b);

        $this->factory->setParameters('Factory\Test\Mocks\TestClassWithDefaultParameter', ['b' => 'baz', 'a' => 'armadillo']);
        $object = $this->factory->get('Factory\Test\Mocks\TestClassWithDefaultParameter');
        $this->assertEquals('baz', $object->b);
    }

    public function testThatNamedDefaultsCanBeOverridden()
    {
        $this->factory->setParameters('Factory\Test\Mocks\TestClassWithDefaultParameter', ['b' => 'banana', 'a' => 'armadillo']);
        $object = $this->factory->get('Factory\Test\Mocks\TestClassWithDefaultParameter', ['b' => 'baz']);

        $this->assertEquals('baz', $object->b);
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testThatAliasesCanOnlyBeString()
    {
        $this->factory->setAlias('Factory\Test\Mocks\TestClass', false);
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testCircularAliasesAreDisallowed()
    {
        $this->factory->setAlias('A', 'B');
        $this->factory->setAlias('B', 'A');
    }

    public function testThatClassesCanBeAliased()
    {
        $this->factory->setAlias('Factory\Test\Mocks\TestClass', 'FooClass');
        $object = $this->factory->get('FooClass', ['armadillo', 'banana']);

        $this->assertInstanceOf('Factory\Test\Mocks\TestClass', $object);
        $this->assertEquals('armadillo', $object->a);
        $this->assertEquals('banana', $object->b);
    }

    public function testThatAliasesCanBeAliased()
    {
        $this->factory->setAlias('Factory\Test\Mocks\TestClass', 'FooClass');
        $this->factory->setAlias('FooClass', 'BarClass');
        $object = $this->factory->get('BarClass', ['armadillo', 'banana']);

        $this->assertInstanceOf('Factory\Test\Mocks\TestClass', $object);
        $this->assertEquals('armadillo', $object->a);
        $this->assertEquals('banana', $object->b);
    }

    public function testThatAliasedClassesUseTheActualClassesParameters()
    {
        $this->factory->setAlias('Factory\Test\Mocks\TestClass', 'FooClass');
        $this->factory->setParameters('Factory\Test\Mocks\TestClass', ['armadillo', 'banana']);
        $object = $this->factory->get('FooClass');

        $this->assertInstanceOf('Factory\Test\Mocks\TestClass', $object);
        $this->assertEquals('armadillo', $object->a);
        $this->assertEquals('banana', $object->b);
    }

    public function testThatInstancesCanBeAdded()
    {
        $std = new \stdClass();
        $this->factory->addInstance($std);
        $object = $this->factory->get('stdClass');

        $this->assertSame($std, $object);
    }

    public function testThatAddInstanceReturnsOld()
    {
        $std = new \stdClass();
        $this->assertNull($this->factory->addInstance($std));
        $this->assertSame($std, $this->factory->addInstance(new \stdClass()));
    }

    public function testThatCallbacksAreFired()
    {
        $called = 0;
        $this->factory->addCallback(
            'stdClass',
            function () use (&$called) {
                $called++;
            }
        );
        $this->factory->addCallback(
            'stdClass',
            function () use (&$called) {
                $called++;
            }
        );
        $this->factory->get('stdClass');
        $this->assertEquals(2, $called);
    }

    /**
     * @expectedException \Factory\Exceptions\InstantiationException
     */
    public function testThatPrivateConstructorThrowsException()
    {
        $this->factory->get('Factory\Test\Mocks\TestClassWithPrivateConstructor');
    }

    /**
     * @expectedException \Factory\Exceptions\InstantiationException
     */
    public function testThatInterfaceThrowsException()
    {
        $this->factory->get('Factory\FactoryInterface');
    }

    /**
     * @expectedException \Factory\Exceptions\InstantiationException
     */
    public function testThatAbstractClassThrowsException()
    {
        $this->factory->get('Factory\Test\Mocks\AbstractTestClass');
    }

    /**
     * @expectedException \Factory\Exceptions\InstantiationException
     */
    public function testThatNotInstantiableRequiredClassesThrowException()
    {
        $this->factory->get('Factory\Test\Mocks\TestClassWithNotDirectlyInstantiableObjectParameter');
    }

    public function testThatNotInstantiableOptionalClassesGetNull()
    {
        $object = $this->factory->get('Factory\Test\Mocks\TestClassWithOptionalNotDirectlyInstantiableObjectParameter');
        $this->assertNull($object->class);
    }

    public function testThatForcedInstanceIsNotStored()
    {
        $this->factory->setParameters('Factory\Test\Mocks\TestClass', ['a' => 'armadillo', 'b' => 'banana']);
        $objectA = $this->factory->get('Factory\Test\Mocks\TestClass', [], true);
        $object = $this->factory->get('Factory\Test\Mocks\TestClass');
        $objectB = $this->factory->get('Factory\Test\Mocks\TestClass', [], true);
        $objectC = $this->factory->get('Factory\Test\Mocks\TestClass');

        $this->assertNotSame($object, $objectA);
        $this->assertNotSame($object, $objectB);
        $this->assertNotSame($objectA, $objectB);
        $this->assertSame($object, $objectC);
    }
}
