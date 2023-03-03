<?php
/**
 * Copyright 2023 Alessio Linares
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

namespace Tests;

use JsonPath\InvalidJsonException;
use JsonPath\InvalidJsonPathException;
use JsonPath\JsonObject;

/**
 * Class JsonObjectTest
 * @author Alessio Linares
 */
class JsonObjectIssue64Test extends \PHPUnit_Framework_TestCase
{

    private $json = '
{
  "success": true,
  "result": [
    {
      "data": {
        "someField": "value1",
        "nestedObj": {
            "someField": "otherValue1"
        }
      }
    },
    {
      "data": {
        "someField": "value2",
        "nestedObj": {
            "someField": "otherValue2"
        }
      }
    },
    {
      "data": {
        "someField": "value3",
        "nestedObj": {
            "someField": "otherValue3"
        }
      }
    }
  ]
}
';

    public function testCase1()
    {
        $jsonObject = new JsonObject($this->json);
        $result = $jsonObject->get('$.result[*].data..someField');
        $expected = ["value1", "otherValue1", "value2", "otherValue2", "value3", "otherValue3"];
        $this->assertEquals($expected, $result);
    }
}
