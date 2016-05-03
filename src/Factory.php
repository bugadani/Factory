<?php

namespace Factory;

use Factory\Exceptions\InstantiationException;

class Factory
{
    /**
     * @var Constructor[]
     */
    private $constructors = [];
    private $storedInstances = [];
    private $classNameMap = [];

    /**
     * @param $className
     */
    protected function createConstructor(string $className)
    {
        $this->constructors[ $className ] = new Constructor($this, $className);
    }

    public function setAlias(string $className, string $alias)
    {
        if (isset($this->classNameMap[ $className ]) && $this->classNameMap[ $className ] === $alias) {
            throw new \InvalidArgumentException(
                "{$className} is already an alias of {$alias}, cannot set the reverse direction"
            );
        }

        $this->classNameMap[ $alias ] = $className;
    }

    public function addInstance($instance)
    {
        $className = get_class($instance);

        if (isset($this->storedInstances[ $className ])) {
            $old = $this->storedInstances[ $className ];
        } else {
            $old = null;
        }

        $this->storedInstances[ $className ] = $instance;

        return $old;
    }

    public function setParameters(string $className, array $parameters)
    {
        if (!isset($this->constructors[ $className ])) {
            $this->createConstructor($className);
        }
        $this->constructors[ $className ]->setParameters($parameters);
    }

    public function addCallback(string $className, callable $callback)
    {
        if (!isset($this->constructors[ $className ])) {
            $this->createConstructor($className);
        }
        $this->constructors[ $className ]->addCallback($callback);
    }

    public function get(string $className, array $arguments = [], bool $forceNew = false)
    {
        //while because 'alias of alias' is allowed
        while (isset($this->classNameMap[ $className ])) {
            $className = $this->classNameMap[ $className ];
        }

        if (isset($this->storedInstances[ $className ]) && !$forceNew) {
            return $this->storedInstances[ $className ];
        }

        if (!isset($this->constructors[ $className ])) {
            $this->createConstructor($className);
        }

        try {
            $object = $this->constructors[ $className ]->instantiate($arguments);
        } catch (\Exception $e) {
            throw new InstantiationException("Could not instantiate {$className}", $e);
        }
        if (!$forceNew) {
            $this->storedInstances[ $className ] = $object;
        }

        return $object;
    }
}