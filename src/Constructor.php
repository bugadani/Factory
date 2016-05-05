<?php

namespace Factory;

use Factory\Exceptions\InstantiationException;
use Factory\Exceptions\ParameterMissingException;

class Constructor
{
    /**
     * @var \ReflectionClass
     */
    private $reflector;

    /**
     * @var string
     */
    private $className;

    /**
     * @var array
     */
    private $parameters = [];

    /**
     * @var Factory
     */
    private $factory;

    /**
     * @var array|null
     */
    private $constructorArgs;

    /**
     * @var callable[]
     */
    private $callbacks = [];

    public function __construct(Factory $factory, string $className)
    {
        $this->className = $className;
        $this->factory   = $factory;
    }

    /**
     * @param array $parameters
     */
    public function setParameters(array $parameters)
    {
        $this->parameters = $parameters + $this->parameters;
    }

    /**
     * @param $positionOrName
     * @param $parameter
     */
    public function setParameter($positionOrName, $parameter)
    {
        $this->parameters[ $positionOrName ] = $parameter;
    }

    public function addCallback(callable $callback)
    {
        $this->callbacks[] = $callback;
    }

    protected function resolveParameter($parameters, \ReflectionParameter $constructorArg)
    {
        $parameterName     = $constructorArg->getName();
        $parameterPosition = $constructorArg->getPosition();

        if (isset($parameters[ $parameterName ])) {
            return $parameters[ $parameterName ];
        } else if (isset($parameters[ $parameterPosition ])) {
            return $parameters[ $parameterPosition ];
        } else if (isset($this->parameters[ $parameterName ])) {
            return $this->parameters[ $parameterName ];
        } else if (isset($this->parameters[ $parameterPosition ])) {
            return $this->parameters[ $parameterPosition ];
        } else if ($constructorArg->getClass() !== null) {
            try {
                return $this->factory->get($constructorArg->getClass()->getName());
            } catch (InstantiationException $e) {
                if ($constructorArg->isDefaultValueAvailable()) {
                    return $constructorArg->getDefaultValue();
                } else {
                    throw $e;
                }
            }
        } else if ($constructorArg->isDefaultValueAvailable()) {
            return $constructorArg->getDefaultValue();
        } else {
            throw new ParameterMissingException($parameterName);
        }
    }

    /**
     * @param \ReflectionParameter[] $constructorParameters
     * @param array $parameters
     *
     * @return array
     */
    private function prepareParameters($constructorParameters, $parameters)
    {
        $return = [];
        foreach ($constructorParameters as $constructorArg) {
            $return[] = $this->resolveParameter($parameters, $constructorArg);
        }

        return $return;
    }

    public function instantiate(array $arguments)
    {
        if (!isset($this->constructorArgs)) {
            //argument handling requires reflection in most cases
            $this->reflector = new \ReflectionClass($this->className);

            if (!$this->reflector->isInstantiable()) {
                //interfaces does not need to be checked because class_exists doesn't allow them to get here
                if ($this->reflector->isAbstract()) {
                    throw new \InvalidArgumentException(
                        "Cannot instantiate {$this->className} because the class is abstract"
                    );
                } else {
                    throw new \InvalidArgumentException("Cannot instantiate {$this->className}");
                }
            }

            $constructor = $this->reflector->getConstructor();

            if ($constructor === null) {
                //This caches the fact that the class has no constructor
                $this->constructorArgs = [];
            } else {
                $this->constructorArgs = $constructor->getParameters();
            }
        }

        if (empty($this->constructorArgs)) {
            //Since the class has no constructor, it can not be instantiated with ReflectionClass

            //Also, if the class has no constructor arguments, it is cheaper to instantiate it directly
            //Although this has the downside of disallowing variable sized argument lists if
            //no arguments are required by the constructor signature.
            $object = new $this->className;
        } else {
            $arguments = $this->prepareParameters($this->constructorArgs, $arguments);

            $object = $this->reflector->newInstanceArgs($arguments);
        }

        foreach ($this->callbacks as $callback) {
            $callback($this->factory, $object);
        }

        return $object;
    }
}