<?php
/**
 * Copyright 2019 Sergey Nikolaev
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

use JsonPath\JsonObject;
use JsonPath\InvalidJsonException;

/**
 * Class JsonObjectLengthOperatorTest
 * @author Sergey Nikolaev
 */
class JsonObjectLengthOperatorTest extends \PHPUnit_Framework_TestCase
{
    private $json = '{
        "music": {
            "bands": [
                {
                    "title": "Godzilla",
                    "genre": "Heavy Metal",
                    "albums": [
                        {
                            "title": "First",
                            "release_date": "2017.01.13",
                            "length": "46:35"
                        },
                        {
                            "title": "Second",
                            "release_date": "2018.02.13",
                            "length": "56:35"
                        },
                        {
                            "title": "Third",
                            "release_date": "2019.03.13",
                            "length": "106:35"
                        }
                    ]
                },
                {
                    "title": "Blue Velvet",
                    "genre": "Hard Rock",
                    "albums": [
                        {
                            "title": "One",
                            "release_date": "2011.07.26",
                            "length": "116:19"
                        },
                        {
                            "title": "Two",
                            "release_date": "2012.08.26",
                            "length": "126:19"
                        },
                        {
                            "title": "Three",
                            "release_date": "2013.09.26",
                            "length": "146:19"
                        }
                    ]
                }
            ],

            "length":[
                {
                    "track": "Simple Thing",
                    "stack": 80
                },
                {
                    "track": "Nobody",
                    "stack": 90
                }
            ]
        }
    }';

    /**
     * @throws InvalidJsonException
     */
    public function testObjectHasField()
    {
        $jsonPath = '$.music.length';
        $jsonObject = new JsonObject($this->json);
        $result = $jsonObject->get($jsonPath);
        $this->assertEquals([
                [
                    [
                        'track' => 'Simple Thing',
                        'stack' => 80
                    ],
                    [
                        'track' => 'Nobody',
                        'stack' => 90
                    ]
                ]
            ],
            $result
        );

        $jsonPath = '$.music.bands[0].albums[0].length';
        $result = $jsonObject->get($jsonPath);
        $this->assertEquals(
            [
                '46:35'
            ],
            $result
        );
    }

    /**
     * @throws InvalidJsonException
     */
    public function testIntermediateLength()
    {
        $jsonPath = '$.music.length[0].track';
        $jsonObject = new JsonObject($this->json);
        $result = $jsonObject->get($jsonPath);

        $this->assertEquals(
            [
                'Simple Thing'
            ],
            $result
        );


        $jsonPath = '$.music.length[1].stack';
        $result = $jsonObject->get($jsonPath);
        $this->assertEquals(
            [
                90
            ],
            $result
        );
    }

    /**
     * @throws InvalidJsonException
     */
    public function testLength()
    {
        /** Array Length Test */
        $jsonPath = '$.music.bands[0].albums.length';
        $jsonObject = new JsonObject($this->json);
        $result = $jsonObject->get($jsonPath);
        $this->assertEquals(
            3,
            $result
        );

        /** String Length Test */
        $jsonPath = '$.music.bands[0].albums[0].length.length';
        $result = $jsonObject->get($jsonPath);
        $this->assertEquals(
            5,
            $result
        );

        $jsonPath = '$.music.bands[0].albums[1].title.length';
        $result = $jsonObject->get($jsonPath);
        $this->assertEquals(
            6,
            $result
        );
    }

    /**
     * @throws InvalidJsonException
     */
    public function testLengthSmartGet()
    {
        /** Array Length Test */
        $jsonPath = '$.music.bands[0].albums.length';
        $jsonObject = new JsonObject($this->json, true);
        $result = $jsonObject->get($jsonPath);
        $this->assertEquals(
            3,
            $result
        );

        /** String Length Test */
        $jsonPath = '$.music.bands[0].albums[0].length.length';
        $result = $jsonObject->get($jsonPath, true);
        $this->assertEquals(
            5,
            $result
        );

        $jsonPath = '$.music.bands[0].albums[1].title.length';
        $result = $jsonObject->get($jsonPath, true);
        $this->assertEquals(
            6,
            $result
        );
    }

    /**
     * @throws InvalidJsonException
     */
    public function testArrayLength()
    {
        /** Arrays Count Length Test */
        $jsonPath = '$.music.bands[*].albums.length';
        $jsonObject = new JsonObject($this->json);
        $result = $jsonObject->get($jsonPath);
        $this->assertEquals(
            [
                3,
                3
            ],
            $result
        );

        /** String Length Test */
        $jsonPath = '$.music.bands[0].albums[*].length.length';
        $result = $jsonObject->get($jsonPath);
        $this->assertEquals(
            [
                5,
                5,
                6
            ],
            $result
        );

        $jsonPath = '$.music.bands[0].albums[*].title.length';
        $result = $jsonObject->get($jsonPath);
        $this->assertEquals(
            [
                5,
                6,
                5
            ],
            $result
        );

        $jsonPath = '$.music.bands[*].albums[*].length.length';
        $result = $jsonObject->get($jsonPath);
        $this->assertEquals(
            [
                5,
                5,
                6,
                6,
                6,
                6
            ],
            $result
        );
    }
}
