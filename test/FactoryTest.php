<?php

declare(strict_types = 1);

namespace Factory\Test;

use Factory\Factory;
use Factory\Test\Mocks\AbstractTestClass;
use Factory\Test\Mocks\TestClass;
use Factory\Test\Mocks\TestClassWithDefaultParameter;
use Factory\Test\Mocks\TestClassWithNotDirectlyInstantiableObjectParameter;
use Factory\Test\Mocks\TestClassWithObjectParameter;
use Factory\Test\Mocks\TestClassWithOptionalNotDirectlyInstantiableObjectParameter;
use Factory\Test\Mocks\TestClassWithPrivateConstructor;

class FactoryTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \Factory\Factory
     */
    private $factory;

    public function setUp()
    {
        $this->factory = new Factory();
    }

    public function testSimpleLoading()
    {
        $object = $this->factory->get(\stdClass::class);

        $this->assertInstanceOf(\stdClass::class, $object);
    }

    /**
     * @expectedException \TypeError
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
        $object = $this->factory->get(TestClass::class, ['armadillo', 'banana']);

        $this->assertEquals('armadillo', $object->a);
        $this->assertEquals('banana', $object->b);
    }

    /**
     * @expectedException \Factory\Exceptions\InstantiationException
     */
    public function testLoadingWithTooFewParameters()
    {
        $this->factory->get(TestClass::class, ['armadillo']);
    }

    /**
     * @expectedException \Factory\Exceptions\InstantiationException
     */
    public function testLoadingWithMultipleMissingParameters()
    {
        $this->factory->get(TestClass::class);
    }

    public function testLoadingWithNamedParametersInjection()
    {
        $object = $this->factory->get(TestClass::class, ['b' => 'banana', 'a' => 'armadillo']);

        $this->assertEquals('armadillo', $object->a);
        $this->assertEquals('banana', $object->b);
    }

    public function testLoadingWithDefaultParameters()
    {
        $object = $this->factory->get(TestClassWithDefaultParameter::class, ['armadillo']);

        $this->assertEquals('armadillo', $object->a);
        $this->assertEquals('foobar', $object->b);
    }

    /**
     * @expectedException \Factory\Exceptions\InstantiationException
     */
    public function testLoadingWithMissingNamedParameters()
    {
        $this->factory->get(TestClass::class, ['b' => 'banana']);
    }

    public function testParameterDefinition()
    {
        $this->factory->setParameters(TestClass::class, ['armadillo', 'banana']);
        $object = $this->factory->get(TestClass::class);

        $this->assertEquals('armadillo', $object->a);
        $this->assertEquals('banana', $object->b);
    }

    public function testNamedParameterDefinition()
    {
        $this->factory->setParameters(TestClass::class, ['b' => 'banana', 'a' => 'armadillo']);
        $object = $this->factory->get(TestClass::class);

        $this->assertEquals('armadillo', $object->a);
        $this->assertEquals('banana', $object->b);
    }

    public function testThatInstancesAreStored()
    {
        $objectA = $this->factory->get(\stdClass::class);
        $objectB = $this->factory->get(\stdClass::class);

        $this->assertSame($objectA, $objectB);

        $this->factory->setParameters(TestClass::class, ['b' => 'banana', 'a' => 'armadillo']);
        $objectA = $this->factory->get(TestClass::class);
        $objectB = $this->factory->get(TestClass::class);

        $this->assertSame($objectA, $objectB);
    }

    public function testThatObjectDependenciesAreInjected()
    {
        $object = $this->factory->get(TestClassWithObjectParameter::class);

        $this->assertInstanceOf('stdClass', $object->class);
    }

    public function testThatStoredObjectsAreInjected()
    {
        $std    = $this->factory->get(\stdClass::class);
        $object = $this->factory->get(TestClassWithObjectParameter::class);

        $this->assertSame($std, $object->class);
    }

    public function testThatDefaultsCanBeOverridden()
    {
        $std    = $this->factory->get(\stdClass::class);
        $object = $this->factory->get(TestClassWithObjectParameter::class, [new \stdClass()]);
        $this->assertNotSame($std, $object->class);

        $object = $this->factory->get(TestClassWithDefaultParameter::class, ['a' => 'armadillo', 'b' => 'baz']);
        $this->assertEquals('baz', $object->b);

        $this->factory->setParameters(TestClassWithDefaultParameter::class, ['b' => 'baz', 'a' => 'armadillo']);
        $object = $this->factory->get(TestClassWithDefaultParameter::class);
        $this->assertEquals('baz', $object->b);
    }

    public function testThatNamedDefaultsCanBeOverridden()
    {
        $this->factory->setParameters(TestClassWithDefaultParameter::class, ['b' => 'banana', 'a' => 'armadillo']);
        $object = $this->factory->get(TestClassWithDefaultParameter::class, ['b' => 'baz']);

        $this->assertEquals('baz', $object->b);
    }

    /**
     * @expectedException \TypeError
     */
    public function testThatAliasesCanOnlyBeString()
    {
        $this->factory->setAlias(TestClass::class, false);
    }

    /**
     * @expectedException \Factory\Exceptions\CircularAliasingException
     */
    public function testCircularAliasesAreDisallowed()
    {
        $this->factory->setAlias('A', 'B');
        $this->factory->setAlias('B', 'A');
    }

    public function testThatClassesCanBeAliased()
    {
        $this->factory->setAlias(TestClass::class, 'FooClass');
        $object = $this->factory->get('FooClass', ['armadillo', 'banana']);

        $this->assertInstanceOf(TestClass::class, $object);
        $this->assertEquals('armadillo', $object->a);
        $this->assertEquals('banana', $object->b);
    }

    public function testThatAliasesCanBeAliased()
    {
        $this->factory->setAlias(TestClass::class, 'FooClass');
        $this->factory->setAlias('FooClass', 'BarClass');
        $object = $this->factory->get('BarClass', ['armadillo', 'banana']);

        $this->assertInstanceOf(TestClass::class, $object);
        $this->assertEquals('armadillo', $object->a);
        $this->assertEquals('banana', $object->b);
    }

    public function testThatAliasedClassesUseTheActualClassesParameters()
    {
        $this->factory->setAlias(TestClass::class, 'FooClass');
        $this->factory->setParameters(TestClass::class, ['armadillo', 'banana']);
        $object = $this->factory->get('FooClass');

        $this->assertInstanceOf(TestClass::class, $object);
        $this->assertEquals('armadillo', $object->a);
        $this->assertEquals('banana', $object->b);
    }

    public function testThatInstancesCanBeAdded()
    {
        $std = new \stdClass();
        $this->factory->addInstance($std);
        $object = $this->factory->get(\stdClass::class);

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
        $incrementCalled = function () use (&$called) {
            $called++;
        };

        //Add multiple callbacks
        $this->factory->addCallback(\stdClass::class, $incrementCalled);
        $this->factory->addCallback(\stdClass::class, $incrementCalled);

        $this->factory->get(\stdClass::class);
        $this->assertEquals(2, $called);
    }

    /**
     * @expectedException \Factory\Exceptions\InstantiationException
     */
    public function testThatPrivateConstructorThrowsException()
    {
        $this->factory->get(TestClassWithPrivateConstructor::class);
    }

    /**
     * @expectedException \Factory\Exceptions\InstantiationException
     */
    public function testThatAbstractClassThrowsException()
    {
        $this->factory->get(AbstractTestClass::class);
    }

    /**
     * @expectedException \Factory\Exceptions\InstantiationException
     */
    public function testThatNotInstantiableRequiredClassesThrowException()
    {
        $this->factory->get(TestClassWithNotDirectlyInstantiableObjectParameter::class);
    }

    public function testThatNotInstantiableOptionalClassesGetNull()
    {
        $object = $this->factory->get(TestClassWithOptionalNotDirectlyInstantiableObjectParameter::class);
        $this->assertNull($object->class);
    }

    public function testThatForcedInstanceIsNotStored()
    {
        $this->factory->setParameters(TestClass::class, ['a' => 'armadillo', 'b' => 'banana']);
        $objectA = $this->factory->get(TestClass::class, [], true);
        $object  = $this->factory->get(TestClass::class);
        $objectB = $this->factory->get(TestClass::class, [], true);
        $objectC = $this->factory->get(TestClass::class);

        $this->assertNotSame($object, $objectA);
        $this->assertNotSame($object, $objectB);
        $this->assertNotSame($objectA, $objectB);
        $this->assertSame($object, $objectC);
    }
}
