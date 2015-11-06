<?php

namespace Factory\Test\Mocks;

class TestClassWithNotDirectlyInstantiableObjectParameter
{
    /**
     * @var TestClass
     */
    public $class;

    public function __construct(TestClass $class)
    {
        $this->class = $class;
    }
}