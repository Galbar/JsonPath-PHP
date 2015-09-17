<?php

namespace JsonPath;

/**
 * Exception that is raised when a error is found in the given JSONPath
 *
 * @uses Exception
 */
class InvalidJsonPathException extends \Exception
{
    private $token;

    /**
     * Class constructor
     *
     * @param string $token token related to the JSONPath error
     *
     * @return void
     */
    public function __construct($token)
    {
        parent::__construct("Error in JSONPath near '" . $token . "'", 0, null);
    }
}
