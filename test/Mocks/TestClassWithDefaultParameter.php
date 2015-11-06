<?php

namespace Factory\Test\Mocks;

class TestClassWithDefaultParameter
{
    public $b;
    public $a;

    public function __construct($a, $b = 'foobar')
    {
        $this->a = $a;
        $this->b = $b;
    }
}