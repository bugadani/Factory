<?php

namespace Factory;

use Factory\Exceptions\InstantiationException;
use ReflectionParameter;

class Factory implements FactoryInterface
{
    private $constructorParameters = [];
    private $storedInstances = [];
    private $classNameMap = [];
    private $callbacks = [];

    public function setAlias($className, $alias)
    {
        $this->stringTypeGuard($className);
        $this->stringTypeGuard($alias, 'alias');

        if(isset($this->classNameMap[$className]) && $this->classNameMap[$className] === $alias) {
            throw new \InvalidArgumentException("{$className} is already an alias of {$alias}, cannot set the reverse direction");
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

    public function setParameters($className, array $parameters)
    {
        $this->stringTypeGuard($className);
        $this->constructorParameters[ $className ] = $parameters;
    }

    public function addCallback($className, callable $callback)
    {
        $this->stringTypeGuard($className);
        if (!isset($this->callbacks[ $className ])) {
            $this->callbacks[ $className ] = [];
        }
        $this->callbacks[ $className ][] = $callback;
    }

    public function get($className, array $arguments = [], $forceNew = false)
    {
        $this->stringTypeGuard($className);

        //while because 'alias of alias' is allowed
        while (isset($this->classNameMap[ $className ])) {
            $className = $this->classNameMap[ $className ];
        }

        if (isset($this->storedInstances[ $className ]) && !$forceNew) {
            return $this->storedInstances[ $className ];
        }

        try {
            $object = $this->instantiate($className, $arguments);
        } catch (\Exception $e) {
            throw new InstantiationException("Could not instantiate {$className}", $e);
        }
        if (isset($this->callbacks[ $className ])) {
            foreach ($this->callbacks[ $className ] as $callback) {
                $callback($this, $object);
            }
        }
        if (!$forceNew) {
            $this->storedInstances[ $className ] = $object;
        }

        return $object;
    }

    /**
     * @param string $className
     * @param \ReflectionParameter[] $constructorParameters
     * @param array $parameters
     * @return mixed
     */
    private function prepareParameters($className, $constructorParameters, $parameters)
    {
        $return  = [];
        $missing = [];

        if (isset($this->constructorParameters[ $className ])) {
            $presetParameters = $this->constructorParameters[ $className ];
        } else {
            $presetParameters = [];
        }

        foreach ($constructorParameters as $constructorArg) {
            $parameterName     = $constructorArg->getName();
            $parameterPosition = $constructorArg->getPosition();

            if (isset($parameters[ $parameterName ])) {
                $return[] = $parameters[ $parameterName ];
            } else if (isset($parameters[ $parameterPosition ])) {
                $return[] = $parameters[ $parameterPosition ];
            } else if (isset($presetParameters[ $parameterName ])) {
                $return[] = $presetParameters[ $parameterName ];
            } else if (isset($presetParameters[ $parameterPosition ])) {
                $return[] = $presetParameters[ $parameterPosition ];
            } else if ($constructorArg->getClass() !== null) {
                try {
                    $return[] = $this->get($constructorArg->getClass()->getName());
                } catch (InstantiationException $e) {
                    if ($constructorArg->isDefaultValueAvailable()) {
                        $return[] = $constructorArg->getDefaultValue();
                    } else {
                        throw $e;
                    }
                }
            } else if ($constructorArg->isDefaultValueAvailable()) {
                $return[] = $constructorArg->getDefaultValue();
            } else {
                $missing[] = $parameterName;
            }
        }

        if (!empty($missing)) {
            if (count($missing) === 1) {
                throw new \InvalidArgumentException("Parameter {$missing[0]} is not set");
            } else {
                $missingParameters = join(', ', $missing);
                throw new \InvalidArgumentException("Parameters {$missingParameters} are not set");
            }
        }

        return $return;
    }

    /**
     * @param $className
     * @param array $arguments
     * @return mixed
     */
    private function instantiate($className, array $arguments)
    {
        if (!class_exists($className)) {
            throw new \InvalidArgumentException("Class {$className} is not found");
        }

        //argument handling requires reflection in most cases
        $reflector = new \ReflectionClass($className);

        if (!$reflector->isInstantiable()) {
            //interfaces does not need to be checked because class_exists doesn't allow them to get here
            if ($reflector->isAbstract()) {
                throw new \InvalidArgumentException("Cannot instantiate {$className} because the class is abstract");
            } else {
                throw new \InvalidArgumentException("Cannot instantiate {$className}");
            }
        }

        $constructor = $reflector->getConstructor();

        if ($constructor === null || $constructor->getNumberOfParameters() === 0) {
            //Since the class has no constructor, it can not be instantiated with ReflectionClass

            //Also, if the class has no constructor arguments, it is cheaper to instantiate it directly
            //Although this has the downside of disallowing variable sized argument lists if
            //no arguments are required by the constructor signature.
            return new $className;
        }

        $constructorArgs = $constructor->getParameters();
        $arguments       = $this->prepareParameters($className, $constructorArgs, $arguments);

        return $reflector->newInstanceArgs($arguments);
    }

    /**
     * @param $className
     * @param $variableName
     */
    private function stringTypeGuard($className, $variableName = 'className')
    {
        if (!is_string($className)) {
            throw new \InvalidArgumentException("\${$variableName} must be of type string");
        }
    }
}