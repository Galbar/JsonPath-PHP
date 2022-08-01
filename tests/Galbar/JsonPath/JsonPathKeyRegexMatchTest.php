<?php
/**
 * Copyright 2019 Vlad Proshin
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
 * Class JsonPathKeyRegexMatchTest
 * @author Vlad Proshin
 */
class JsonPathKeyRegexMatchTest extends \PHPUnit_Framework_TestCase
{
    private $json = '
{
    "store": {
        "book": [
            {
                "category": "reference",
                "author": "Nigel Rees",
                "title": "Sayings of the Century",
                "price": 8.95,
                "available": true
            },
            {
                "category": "fiction",
                "author": "Evelyn Waugh",
                "title": "Sword of Honour",
                "price": 12.99,
                "available": false
            },
            {
                "category": "fiction",
                "author": "Herman Melville",
                "title": "Moby Dick",
                "isbn": "0-553-21311-3",
                "price": 8.99,
                "available": true
            },
            {
                "category": "fiction",
                "author": "J. R. R. Tolkien",
                "title": "The Lord of the Rings",
                "isbn": "0-395-19395-8",
                "price": 22.99,
                "available": false
            }
        ],
        "bicycle": {
            "color": "red",
            "price": 19.95,
            "available": true
        },
        "motorcycle": {
            "color": "blue",
            "price": 999.99,
            "available": false
        }
    },
    "authors": [
        "Nigel Rees",
        "Evelyn Waugh",
        "Herman Melville",
        "J. R. R. Tolkien"
    ],
    "author-link": {
        "Nigel Rees": {
            "href": "https://en.wikipedia.org/wiki/Nigel_Rees",
            "placed-at": "wikipedia"
        },
        "Evelyn Waugh": {
            "href": "https://www.britannica.com/biography/Evelyn-Waugh",
            "placed-at": "britannica"
        },
        "Herman Melville": {
            "href": "https://www.britannica.com/biography/Herman-Melville",
            "placed-at": "britannica"
        },
        "J. R. R. Tolkien": {
            "href": "https://www.tolkiensociety.org/author/biography/",
            "placed-at": "tolkiensociety"
        }
    },
    "author-biography": [
        {
            "Nigel Rees": [
                {
                    "born": "5 June 1944",
                    "age": 78
                }
            ],
            "Evelyn Waugh": [
                {
                    "born": "28 October 1903",
                    "died": "10 April 1966",
                    "resting-place": "Combe Florey"
                }
            ],
            "Herman Melville": [
                {
                    "born": "August 1, 1819",
                    "died": "September 28, 1891",
                    "resting-place": "Woodlawn"
                }
            ],
            "J. R. R. Tolkien": [
                {
                    "born": "3 January 1892",
                    "died": "2 September 1973",
                    "resting-place": "Bournemouth"
                }
            ]
        }
    ]
}
';

    /**
     * @throws InvalidJsonException
     */
    public function testRootObjectKeyMatch()
    {
        $jsonPath = '$[?(/^sto?re+$/)].bicycle.price';
        $jsonObject = new JsonObject($this->json);
        $result = $jsonObject->get($jsonPath);
        $this->assertEquals(
            [19.95],
            $result
        );

        $jsonPath = '$[?(/\-link$/)].*.placed-at';
        $result = $jsonObject->get($jsonPath);
        $this->assertEquals([
                "wikipedia",
                "britannica",
                "britannica",
                "tolkiensociety"
            ],
            $result
        );
    }

    /**
     * @throws InvalidJsonException
     */
    public function testRootArrayKeyMatch()
    {
        $jsonPath = '$[?(/auth.*s$/)]';
        $jsonObject = new JsonObject($this->json);
        $result = $jsonObject->get($jsonPath);
        $this->assertEquals([
                [
                    "Nigel Rees",
                    "Evelyn Waugh",
                    "Herman Melville",
                    "J. R. R. Tolkien"
                ]
            ],
            $result
        );

        $jsonPath = '$[?(/^\w*\-biography$/)]..*.age';
        $result = $jsonObject->get($jsonPath);
        $this->assertEquals(
            [78],
            $result
        );
    }

    /**
     * @throws InvalidJsonException
     */
    public function testNestedObjectKeyMatch()
    {
        $jsonPath = '$..*[?(/^\w*[^-]cycle$/)].color';
        $jsonObject = new JsonObject($this->json);
        $result = $jsonObject->get($jsonPath);
        $this->assertEquals(
            ["red", "blue"],
            $result
        );

        $jsonPath = '$.*[?(/^[A-Z]erman\sMelvil{2}e$/)].href';
        $result = $jsonObject->get($jsonPath);
        $this->assertEquals(
            ["https://www.britannica.com/biography/Herman-Melville"],
            $result
        );

        $jsonPath = '$.*[?(/^[A-Z]erman\sMelvil{2}e$/)][0]';
        $result = $jsonObject->get($jsonPath);
        $this->assertEquals(
            false,
            $result
        );
    }


    /**
     * @throws InvalidJsonException
     */
    public function testNestedArrayKeyMatch()
    {
        $jsonPath = '$.*[?(/^bo{2}k$/)][*].isbn';
        $jsonObject = new JsonObject($this->json);
        $result = $jsonObject->get($jsonPath);
        $this->assertEquals(
            ["0-553-21311-3", "0-395-19395-8"],
            $result
        );

        $jsonPath = '$.*[?(/R\.?/)][0]';
        $result = $jsonObject->get($jsonPath);
        $this->assertEquals(
            false,
            $result
        );

        $jsonPath = '$..*[?(/R\.?/)][0].born';
        $result = $jsonObject->get($jsonPath);
        $this->assertEquals(
            ["5 June 1944", "3 January 1892"],
            $result
        );

        $jsonPath = '$..*[?(/^[A-Z]\w*\s+[A-Z]\w*$/)][*].resting-place';
        $result = $jsonObject->get($jsonPath);
        $this->assertEquals(
            ["Combe Florey", "Woodlawn"],
            $result
        );
    }

    /**
     * @throws InvalidJsonException
     */
    public function testRecursiveKeyMatch()
    {
        $jsonPath = '$..*[?(/^author|age|died$/)]';
        $jsonObject = new JsonObject($this->json);
        $result = $jsonObject->get($jsonPath);
        $this->assertEquals([
                "Nigel Rees",
                "Evelyn Waugh",
                "Herman Melville",
                "J. R. R. Tolkien",
                78,
                "10 April 1966",
                "September 28, 1891",
                "2 September 1973"
            ],
            $result
        );
    }

    /**
     * @throws InvalidJsonException
     */
    public function testPcreCaseInsensitiveModifier()
    {
        $jsonPath = '$.*[?(/^evelyn.waugh$/i)].href';
        $jsonObject = new JsonObject($this->json);
        $result = $jsonObject->get($jsonPath);
        $this->assertEquals(
            ["https://www.britannica.com/biography/Evelyn-Waugh"],
            $result
        );

        $jsonPath = '$.*[?(/^evelyn.waugh$/)].href';
        $result = $jsonObject->get($jsonPath);
        $this->assertEquals(
            false,
            $result
        );
    }

    /**
     * @throws InvalidJsonException
     */
    public function testPcreIgnoreWhiteSpaceModifier()
    {
        $jsonPath = '$.*[?(/^J. R. R. Tolkien$/)].href';
        $jsonObject = new JsonObject($this->json);
        $result = $jsonObject->get($jsonPath);
        $this->assertEquals(
            ["https://www.tolkiensociety.org/author/biography/"],
            $result
        );

        $jsonPath = '$.*[?(/^J. R. R. Tolkien$/x)].href';
        $result = $jsonObject->get($jsonPath);
        $this->assertEquals(
            false,
            $result
        );

        $jsonPath = '$.*[?(/^  J.\s  R.\s  R.\s Tolkien  $/x)].href';
        $result = $jsonObject->get($jsonPath);
        $this->assertEquals(
            ["https://www.tolkiensociety.org/author/biography/"],
            $result
        );
    }
}
