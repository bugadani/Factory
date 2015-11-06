Factory
========

Factory is a simple PHP dependency injection container for PHP 5.4 and newer.

Usage
--------

To instantiate an object, simply pass its name to Factory::get(). The instantiated objects are stored and can be retrieved later.

    $instance = $factory->get(FooClass::class);

Class dependencies are resolved automatically.

    class FooClass {
        public function __construct(BarClass $b){
            //$b will be injected
        }
    }
    $instance = $factory->get(FooClass::class);

Other constructor parameters may be defined by passing an array to either Factory::setParameters(), or as the second argument of Factory::get().
These parameters may be both positional or named. Named parameters have precedence over positional ones and parameters passed to Factory::get() will
override the ones set via Factory::setParameters().

    class FooClass {
        public function __construct($a, $b){
            //$a == 'foobar'
            //$b == 'baz'
        }
    }
    $factory->setParameters(FooClass::class, ['b' => 'baz', 0 => 'foobar']);
    $instance = $factory->get(FooClass::class);

Class names may also be aliased, which may be useful to inject interface implementations or extended classes.
Aliased classes use the actual class' parameters.

    class FooClass {
        public function __construct(SomeInterface $a){
            //$a instanceof SomeInterfaceImpl
        }
    }
    $factory->addAlias(SomeInterfaceImpl::class, SomeInterface::class);
    $instance = $factory->get(FooClass::class);

It is possible to set callback functions to be called when an object is instantiated to do some additional initialization.
The Factory instance and the object is passed to the callback. This callback will only be called if a new object is created.

    $factory->addCallback(SomeClass::class, function(Factory $factory, SomeClass $instance){});
    $instance = $factory->get(SomeClass::class);