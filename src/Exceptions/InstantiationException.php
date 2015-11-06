<?php

namespace Factory\Exceptions;

class InstantiationException extends \RuntimeException
{

    /**
     * InstantiationException constructor.
     * @param string $message
     * @param \Exception $previous
     */
    public function __construct($message, \Exception $previous)
    {
        parent::__construct($message, 0, $previous);
    }
}