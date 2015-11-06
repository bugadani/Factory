<?php

namespace Factory\Test\Mocks;

class TestClassWithOptionalNotDirectlyInstantiableObjectParameter
{
    /**
     * @var TestClass
     */
    public $class;

    public function __construct(TestClass $class = null)
    {
        $this->class = $class;
    }
}