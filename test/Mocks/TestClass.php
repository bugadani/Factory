<?php

namespace Factory\Test\Mocks;

class TestClass
{
    public $b;
    public $a;

    public function __construct($a, $b)
    {
        $this->a = $a;
        $this->b = $b;
    }
}