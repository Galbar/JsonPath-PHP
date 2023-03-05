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
class JsonObjectIssue37Test extends \PHPUnit_Framework_TestCase
{
    public function testCase1()
    {
        $jsonObject = new JsonObject('{"a": "first", "2": "second", "b": "third"}');
        $result = $jsonObject->get('$.2');
        $expected = ["second"];
        $this->assertEquals($expected, $result);
    }

    public function testCase2()
    {
        $jsonObject = new JsonObject('["first", "second"]');
        $result = $jsonObject->get('$[0:0]');
        $expected = [];
        $this->assertEquals($expected, $result);
    }

    public function testCase3()
    {
        $jsonObject = new JsonObject('[{"key": 0}, {"key": 42}, {"key": -1}, {"key": 41}, {"key": 43}, {"key": 42.0001}, {"key": 41.9999}, {"key": 100}, {"some": "value"}]');
        $result = $jsonObject->get('$[?(@.key<42)]');
        $expected = [["key"=> 0], ["key"=> -1], ["key"=> 41], ["key"=> 41.9999]];
        $this->assertEquals($expected, $result);
    }

    public function testCase4()
    {
        $jsonObject = new JsonObject('{"key": "value", "another key": {"complex": "string", "primitives": [0, 1]}}');
        $result = $jsonObject->get('$..*');
        $expected = array (
            "value",
            array (
                "complex"=> "string",
                "primitives" => array(
                    0,
                    1
                )
            ),
            "string",
            array(0, 1),
            0,
            1
        );
        $this->assertEquals($expected, $result);
    }

    public function testCase5()
    {
        $jsonObject = new JsonObject('[40, null, 42]');
        $result = $jsonObject->get('$..*');
        $expected = array(
            40,
            null,
            42
        );
        $this->assertEquals($expected, $result);
    }

    public function testCase6()
    {
        $jsonObject = new JsonObject('42');
        $result = $jsonObject->get('$..*');
        $expected = [];
        $this->assertEquals($expected, $result);
    }
}
