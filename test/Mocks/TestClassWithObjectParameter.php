<?php

namespace Factory\Test\Mocks;

class TestClassWithObjectParameter
{
    /**
     * @var \stdClass
     */
    public $class;

    public function __construct(\stdClass $class)
    {
        $this->class = $class;
    }
}