<?php
/**
 * Copyright 2018 Alessio Linares
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

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
