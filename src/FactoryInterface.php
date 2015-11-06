<?php

namespace Factory;

interface FactoryInterface
{
    public function get($className, array $arguments = [], $forceNew = false);

    public function setParameters($className, array $parameters);

    public function setAlias($className, $alias);

    public function addInstance($instance);

    public function addCallback($className, callable $callback);
}