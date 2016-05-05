<?php

namespace Factory\Exceptions;

class ParameterMissingException extends \Exception
{

    /**
     * ParameterMissingException constructor.
     * @param $parameterName
     */
    public function __construct($parameterName)
    {
        parent::__construct("Parameter not set: {$parameterName}");
    }
}