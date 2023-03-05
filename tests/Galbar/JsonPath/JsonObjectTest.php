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

namespace Tests;

use JsonPath\InvalidJsonException;
use JsonPath\InvalidJsonPathException;
use JsonPath\JsonObject;

/**
 * Class JsonObjectTest
 * @author Alessio Linares
 */
class JsonObjectTest extends \PHPUnit_Framework_TestCase
{
    private $json = '
{ "store": {
    "book": [
      { "category": "reference",
        "author": "Nigel Rees",
        "title": "Sayings of the Century",
        "price": 8.95,
        "available": true
      },
      { "category": "fiction",
        "author": "Evelyn Waugh",
        "title": "Sword of Honour",
        "price": 12.99,
        "available": false
      },
      { "category": "fiction",
        "author": "Herman Melville",
        "title": "Moby Dick",
        "isbn": "0-553-21311-3",
        "price": 8.99,
        "available": true
      },
      { "category": "fiction",
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
      "available": true,
      "model": null,
      "sku-number": "BCCLE-0001-RD"
    }
  },
  "authors": [
    "Nigel Rees",
    "Evelyn Waugh",
    "Herman Melville",
    "J. R. R. Tolkien"
  ],
  "Bike models": [
    1,
    2,
    3
  ]
}
';

    /**
     * testGet
     *
     * @param array $expected expected
     * @param array $jsonObject jsonObject
     * @param string $jsonPath jsonPath
     *
     * @return void
     *
     * @dataProvider testGetProvider
     */
    public function testGet($expected, $jsonPath, $testReference = true)
    {
        $jsonObject = new JsonObject($this->json);
        $result = $jsonObject->get($jsonPath);
        $this->assertEquals($expected, $result);

        if ($result !== false && $testReference) {
            // Test that all elements in the result are
            // references to the contents in the object
            foreach ($result as &$element) {
                $element = 'NaN';
            }

            $result2 = $jsonObject->get($jsonPath);
            foreach ($result2 as &$element) {
                $this->assertEquals('NaN', $element);
            }
        }
    }

    public function testGetProvider()
    {
        return array(
            array(
                json_decode('[
                    {"category": "reference",
                    "author": "Nigel Rees",
                    "title": "Sayings of the Century",
                    "price": 8.95,
                    "available": true
                    },
                    {"category": "fiction",
                    "author": "Herman Melville",
                    "title": "Moby Dick",
                    "isbn": "0-553-21311-3",
                    "price": 8.99,
                    "available": true},
                    {"category": "fiction",
                    "author": "J. R. R. Tolkien",
                    "title": "The Lord of the Rings",
                    "isbn": "0-395-19395-8",
                    "price": 22.99,
                    "available": false}
                ]', true),
                "$.store.book[-4, -2, -1]"
            ),
            array(
                array(19.95),
                "$.store.bicycle.price"
            ),
            array(
                array("BCCLE-0001-RD"),
                "$.store.bicycle.sku-number"
            ),
            array(
                array(
                    array(
                        "color" => "red",
                        "price" => 19.95,
                        "available" => true,
                        "model" => null,
                        "sku-number" => "BCCLE-0001-RD"
                    )
                ),
                "$.store.bicycle"
            ),
            array(
                [],
                "$.store.bicycl"
            ),
            array(
                array(
                    8.95,
                    12.99,
                    8.99,
                    22.99
                ),
                "$.store.book[*].price"
            ),
            array(
                [],
                "$.store.book[7]"
            ),
            array(
                array(
                    12.99,
                    8.99
                ),
                "$.store.book[1, 2].price"
            ),
            array(
                array(
                    'reference',
                    'Nigel Rees',
                    'fiction',
                    'Evelyn Waugh',
                    'fiction',
                    'Herman Melville',
                    'fiction',
                    'J. R. R. Tolkien'
                ),
                "$.store.book[*][category, author]"
            ),
            array(
                array(
                    'reference',
                    'Nigel Rees',
                    'fiction',
                    'Evelyn Waugh',
                    'fiction',
                    'Herman Melville',
                    'fiction',
                    'J. R. R. Tolkien'
                ),
                "$.store.book[*]['category', \"author\"]"
            ),
            array(
                array(
                    8.95,
                    8.99
                ),
                "$.store.book[0:3:2].price"
            ),
            array(
                array(
                    8.95,
                    12.99
                ),
                "$.store.book[:2].price"
            ),
            array(
                [],
                "$.store.bicycle.price[2]"
            ),
            array(
                [],
                "$.store.bicycle.price.*"
            ),
            array(
                array(
                    "red",
                    19.95,
                    true,
                    null,
                    "BCCLE-0001-RD"
                ),
                "$.store.bicycle.*"
            ),
            array(
                array(
                    19.95,
                    8.95,
                    12.99,
                    8.99,
                    22.99
                ),
                "$..*.price"
            ),
            array(
                array(
                    12.99,
                    8.99,
                    22.99
                ),
                "$.store.book[?(@.category == 'fiction')].price"
            ),
            array(
                array(
                    19.95,
                    8.95,
                    8.99
                ),
                "$..*[?(@.available == true)].price"
            ),
            array(
                array(
                    12.99,
                    22.99
                ),
                "$..*[?(@.available == false)].price"
            ),
            array(
                array(
                    "Sayings of the Century",
                    "Moby Dick"
                ),
                "$..*[?(@.price < 10)].title"
            ),
            array(
                array(
                    "Sayings of the Century",
                    "Moby Dick"
                ),
                "$..*[?(@.price < 10.0)].title"
            ),
            array(
                array(
                    "Sword of Honour",
                    "The Lord of the Rings"
                ),
                "$.store.book[?(@.price > 10)].title"
            ),
            array(
                array(
                    "The Lord of the Rings"
                ),
                "$..*[?(@.author =~ /.*Tolkien/)].title"
            ),
            array(
                array(
                    "The Lord of the Rings"
                ),
                "$..*[?(@.author =~ /.*tolkien/i)].title"
            ),
            array(
                array(
                    "The Lord of the Rings"
                ),
                "$..*[?(@.author =~ /  J.\ R.\ R.\ Tolkien  /x)].title"
            ),
            array(
                array(
                    "red"
                ),
                "$..*[?(@.length <= 5)].color"
            ),
            array(
                array(
                    "red"
                ),
                "$..*[?(@.length <= 5.0)].color"
            ),
            array(
                array(
                    "The Lord of the Rings"
                ),
                "$.store.book[?(@.author == $.authors[3])].title"
            ),
            array(
                array(
                    "red",
                    "J. R. R. Tolkien"
                ),
                "$..*[?(@.price >= 19.95)][author, color]"
            ),
            array(
                array(
                    19.95,
                    8.99
                ),
                "$..*[?(@.category == 'fiction' and @.price < 10 or @.color == \"red\")].price"
            ),
            array(
                array(
                    19.95,
                    8.99
                ),
                "$..*[?(@.category == 'fiction' && @.price < 10 || @.color == \"red\")].price"
            ),
            array(
                array(
                    8.95
                ),
                "$.store.book[?(not @.category == 'fiction')].price"
            ),
            array(
                array(
                    8.95
                ),
                "$.store.book[?(! @.category == 'fiction')].price"
            ),
            array(
                array(
                    8.95
                ),
                "$.store.book[?(@.category != 'fiction')].price"
            ),
            array(
                array(
                    "red"
                ),
                "$..*[?(@.color)].color"
            ),
            array(
                array(
                    true
                ),
                "$.store[?(not @..price or @..color == 'red')].available"
            ),
            array(
                array(
                    true
                ),
                "$.store[?(! @..price or @..color == 'red')].available"
            ),
            array(
                array(
                    true
                ),
                "$.store[?(not @..price || @..color == 'red')].available"
            ),
            array(
                array(
                    true
                ),
                "$.store[?(! @..price || @..color == 'red')].available"
            ),
            array(
                [],
                "$.store[?(@.price.length == 3)]"
            ),
            array(
                array(
                    19.95
                ),
                "$.store[?(@.color.length == 3)].price"
            ),
            array(
                [],
                "$.store[?(@.color.length == 5)].price"
            ),
            array(
                array(
                    array(
                        "color" => "red",
                        "price" => 19.95,
                        "available" => true,
                        "model" => null,
                        "sku-number" => "BCCLE-0001-RD"
                    )
                ),
                "$.store[?(@.*.length == 3)]",
                false
            ),
            array(
                array(
                    "red"
                ),
                "$.store..[?(@..model == null)].color"
            ),
            array(
                array(
                    array(1, 2, 3)
                ),
                "$['Bike models']"
            ),
            array(
                array(
                    array(1, 2, 3)
                ),
                '$["Bike models"]'
            )
        );
    }

    /**
     * testSmartGet
     *
     * @param array $expected expected
     * @param string $jsonPath jsonPath
     *
     * @return void
     *
     * @dataProvider testSmartGetProvider
     */
    public function testSmartGet($expected, $jsonPath)
    {
        $jsonObject = new JsonObject($this->json, true);
        $result = $jsonObject->get($jsonPath);
        $this->assertEquals($expected, $result);
    }

    public function testSmartGetProvider()
    {
        return array(
            array(
                19.95,
                "$.store.bicycle.price"
            ),
            array(
                array(
                    "color" => "red",
                    "price" => 19.95,
                    "available" => true,
                    "model" => null,
                    "sku-number" => "BCCLE-0001-RD"
                ),
                "$.store.bicycle"
            ),
            array(
                false,
                "$.store.bicycl"
            ),
            array(
                array(
                    8.95,
                    12.99,
                    8.99,
                    22.99
                ),
                "$.store.book[*].price"
            ),
            array(
                false,
                "$.store.book[7]"
            ),
            array(
                [],
                "$.store.book[7, 9]"
            ),
            array(
                array(
                    12.99,
                    8.99
                ),
                "$.store.book[1, 2].price"
            ),
            array(
                array(
                    'reference',
                    'Nigel Rees',
                    'fiction',
                    'Evelyn Waugh',
                    'fiction',
                    'Herman Melville',
                    'fiction',
                    'J. R. R. Tolkien'
                ),
                "$.store.book[*][category, author]"
            ),
            array(
                array(
                    'reference',
                    'Nigel Rees',
                    'fiction',
                    'Evelyn Waugh',
                    'fiction',
                    'Herman Melville',
                    'fiction',
                    'J. R. R. Tolkien'
                ),
                "$.store.book[*]['category', \"author\"]"
            ),
            array(
                array(
                    8.95,
                    8.99
                ),
                "$.store.book[0:3:2].price"
            ),
            array(
                false,
                "$.store.bicycle.price[2]"
            ),
            array(
                [],
                "$.store.bicycle.price.*"
            ),
            array(
                array(
                    "red",
                    19.95,
                    true,
                    null,
                    "BCCLE-0001-RD"
                ),
                "$.store.bicycle.*"
            ),
            array(
                array(
                    19.95,
                    8.95,
                    12.99,
                    8.99,
                    22.99
                ),
                "$..*.price"
            ),
            array(
                array(
                    12.99,
                    8.99,
                    22.99
                ),
                "$.store.book[?(@.category == 'fiction')].price"
            ),
            array(
                array(
                    19.95,
                    8.95,
                    8.99
                ),
                "$..*[?(@.available == true)].price"
            ),
            array(
                array(
                    12.99,
                    22.99
                ),
                "$..*[?(@.available == false)].price"
            ),
            array(
                array(
                    "Sayings of the Century",
                    "Moby Dick"
                ),
                "$..*[?(@.price < 10)].title"
            ),
            array(
                array(
                    "Sayings of the Century",
                    "Moby Dick"
                ),
                "$..*[?(@.price < 10.0)].title"
            ),
            array(
                array(
                    "Sword of Honour",
                    "The Lord of the Rings"
                ),
                "$.store.book[?(@.price > 10)].title"
            ),
            array(
                array(
                    "The Lord of the Rings"
                ),
                "$..*[?(@.author =~ /.*Tolkien/)].title"
            ),
            array(
                array(
                    "red"
                ),
                "$..*[?(@.length <= 5)].color"
            ),
            array(
                array(
                    "red"
                ),
                "$..*[?(@.length <= 5.0)].color"
            ),
            array(
                array(
                    "The Lord of the Rings"
                ),
                "$.store.book[?(@.author == $.authors[3])].title"
            ),
            array(
                array(
                    "red",
                    "J. R. R. Tolkien"
                ),
                "$..*[?(@.price >= 19.95)][author, color]"
            ),
            array(
                array(
                    19.95,
                    8.99
                ),
                "$..*[?(@.category == 'fiction' and @.price < 10 or @.color == \"red\")].price"
            ),
            array(
                array(
                    19.95,
                    8.99
                ),
                "$..*[?(@.category == 'fiction' && @.price < 10 || @.color == \"red\")].price"
            ),
            array(
                array(
                    8.95
                ),
                "$.store.book[?(not @.category == 'fiction')].price"
            ),
            array(
                array(
                    8.95
                ),
                "$.store.book[?(! @.category == 'fiction')].price"
            ),
            array(
                array(
                    8.95
                ),
                "$.store.book[?(@.category != 'fiction')].price"
            ),
            array(
                array(
                    "red"
                ),
                "$..*[?(@.color)].color"
            ),
            array(
                array(
                    true
                ),
                "$.store[?(not @..price or @..color == 'red')].available"
            ),
            array(
                array(
                    true
                ),
                "$.store[?(! @..price or @..color == 'red')].available"
            ),
            array(
                array(
                    true
                ),
                "$.store[?(not @..price || @..color == 'red')].available"
            ),
            array(
                array(
                    true
                ),
                "$.store[?(! @..price || @..color == 'red')].available"
            ),
            array(
                [],
                "$.store[?(@.price.length == 3)]"
            ),
            array(
                array(
                    19.95
                ),
                "$.store[?(@.color.length == 3)].price"
            ),
            array(
                [],
                "$.store[?(@.color.length == 5)].price"
            ),
            array(
                array(
                    array(
                        "color" => "red",
                        "price" => 19.95,
                        "available" => true,
                        "model" => null,
                        "sku-number" => "BCCLE-0001-RD"
                    )
                ),
                "$.store[?(@.*.length == 3)]",
                false
            ),
            array(
                array(
                    "red"
                ),
                "$.store..[?(@..model == null)].color"
            ),
            array(
                array(1, 2, 3),
                "$['Bike models']"
            ),
            array(
                array(1, 2, 3),
                '$["Bike models"]'
            ),
            array(
                array(
                    array(
                        "category" => "fiction",
                        "author" => "Evelyn Waugh",
                        "title" => "Sword of Honour",
                        "price" => 12.99,
                        "available" => false
                    ),
                    array(
                        "category" => "fiction",
                        "author" => "J. R. R. Tolkien",
                        "isbn" => "0-395-19395-8",
                        "title" => "The Lord of the Rings",
                        "price" => 22.99,
                        "available" => false
                    )
                ),
                "$.store.book[?(@.title in ['Sword of Honour', 'The Lord of the Rings'])]",
            ),
            array(
                array(
                    array(
                        "category" => "reference",
                        "author" => "Nigel Rees",
                        "title" => "Sayings of the Century",
                        "price" => 8.95,
                        "available" => true,
                    ),
                    array(
                        "category" => "fiction",
                        "author" => "Evelyn Waugh",
                        "title" => "Sword of Honour",
                        "price" => 12.99,
                        "available" => false,
                    ),
                    array(
                        "category" => "fiction",
                        "author" => "J. R. R. Tolkien",
                        "title" => "The Lord of the Rings",
                        "isbn" => "0-395-19395-8",
                        "price" => 22.99,
                        "available" => false,
                    ),
                ),
                "$.store.book[?(@.author == 'Nigel Rees' or @.title in ['Sword of Honour', 'The Lord of the Rings'])]",
            ),
            array(
                array(
                    array(
                        "category" => "fiction",
                        "author" => "Herman Melville",
                        "title" => "Moby Dick",
                        "isbn" => "0-553-21311-3",
                        "price" => 8.99,
                        "available" => true,
                    )
                ),
                "$.store.book[?(@.isbn in ['0-553-21311-3', '0-395-19395-8'] and @.available == true)]"
            ),
            array(
                array(
                    array(
                        "category" => "reference",
                        "author" => "Nigel Rees",
                        "title" => "Sayings of the Century",
                        "price" => 8.95,
                        "available" => true,
                    ),
                    array(
                        "category" => "fiction",
                        "author" => "Herman Melville",
                        "title" => "Moby Dick",
                        "isbn" => "0-553-21311-3",
                        "price" => 8.99,
                        "available" => true,
                    )
                ),
                "$.store.book[?(not @.title in ['Sword of Honour', 'The Lord of the Rings'])]"
            ),
            array(
                array(
                    array(
                        "category" => "fiction",
                        "author" => "Evelyn Waugh",
                        "title" => "Sword of Honour",
                        "price" => 12.99,
                        "available" => false,
                    )
                ),
                "$.store.book[?(not @.isbn in ['0-553-21311-3', '0-395-19395-8'] and @.available == false)]"
            ),
            array(
                array(
                    array(
                        "category" => "reference",
                        "author" => "Nigel Rees",
                        "title" => "Sayings of the Century",
                        "price" => 8.95,
                        "available" => true,
                    ),
                    array(
                        "category" => "fiction",
                        "author" => "Evelyn Waugh",
                        "title" => "Sword of Honour",
                        "price" => 12.99,
                        "available" => false,
                    )
                ),
                "$.store.book[?(@.price in [12.99, 8.95])]"
            ),
            array(
                array(
                    array(
                        "category" => "reference",
                        "author" => "Nigel Rees",
                        "title" => "Sayings of the Century",
                        "price" => 8.95,
                        "available" => true,
                    ),
                    array(
                        "category" => "fiction",
                        "author" => "Herman Melville",
                        "title" => "Moby Dick",
                        "isbn" => "0-553-21311-3",
                        "price" => 8.99,
                        "available" => true,
                    )
                ),
                "$.store.book[?(@.available in [ true ])]"
            ),
            array(
                array(
                    array(
                        "category" => "reference",
                        "author" => "Nigel Rees",
                        "title" => "Sayings of the Century",
                        "price" => 8.95,
                        "available" => true,
                    ),
                    array(
                        "category" => "fiction",
                        "author" => "Herman Melville",
                        "title" => "Moby Dick",
                        "isbn" => "0-553-21311-3",
                        "price" => 8.99,
                        "available" => true,
                    )
                ),
                "$..book[?(@.author in [$.authors[0], $.authors[2]])]"
            )
        );
    }

    public function testWithSmartGet()
    {
        return array(
            array(true),
            array(false)
        );
    }

    /**
     * testSet
     *
     * @param bool $smartGet smartGet
     *
     * @return void
     * @dataProvider testWithSmartGet
     */
    public function testSet($smartGet)
    {
        $jsonObject = new JsonObject($this->json, $smartGet);
        $jsonObject->set('$.authors', array("Patrick Rothfuss", "Trudi Canavan"));
        $this->assertEquals(array("Patrick Rothfuss", "Trudi Canavan"), $jsonObject->get('$.authors[*]'));
        $jsonObject->set('$.store.car[0,1].type', 'sport');
        $jsonObject->set('$.store[pen, pencil].price', 0.99);
        $this->assertEquals(
            array(
                0.99,
                0.99
            ),
            $jsonObject->get('$.store[pen, pencil].price')
        );
        if ($smartGet) {
            $this->assertEquals(
                array(
                    array(
                        'type' => 'sport'
                    ),
                    array(
                        'type' => 'sport'
                    )
                ),
                $jsonObject->get('$.store.car')
            );
        } else {
            $this->assertEquals(
                array(
                    array(
                        array(
                            'type' => 'sport'
                        ),
                        array(
                            'type' => 'sport'
                        )
                    )
                ),
                $jsonObject->get('$.store.car')
            );
        }
    }

    public function testGetValue()
    {
        $array = json_decode($this->json, true);
        $jsObject = new JsonObject($array);
        $this->assertEquals($array, $jsObject->getValue());
        $jsObject = new JsonObject($this->json);
        $this->assertEquals($array, $jsObject->getValue());
        $object = json_decode($this->json);
        $jsObject = new JsonObject($object);
        $this->assertEquals($array, $jsObject->getValue());
    }

    /**
     * testAdd
     *
     * @param bool $smartGet smartGet
     *
     * @return void
     * @dataProvider testWithSmartGet
     */
    public function testAdd($smartGet)
    {
        $jsonObject = new JsonObject($this->json, $smartGet);
        $jsonObject->add('$.authors', 'Trudi Canavan');
        $this->assertEquals(array("Nigel Rees", "Evelyn Waugh", "Herman Melville", "J. R. R. Tolkien", "Trudi Canavan"), $jsonObject->get('$.authors[*]'));

        $jsonObject->add('$.store.bicycle', 'BMX', 'type');
        $expected = array(
            array(
                'color' => 'red',
                'price' => 19.95,
                'type' => 'BMX',
                'available' => true,
                'model' => null,
                "sku-number" => "BCCLE-0001-RD"
            )
        );
        $expected = $smartGet ? $expected[0] : $expected;
        $this->assertEquals(
            $expected,
            $jsonObject->get('$.store.bicycle')
        );
    }

    /**
     * testRemove
     *
     * @param bool $smartGet smartGet
     *
     * @return void
     * @dataProvider testWithSmartGet
     */
    public function testRemove($smartGet)
    {
        $jsonObject = new JsonObject($this->json, $smartGet);
        $jsonObject->remove('$..*[?(@.price)]', 'price')->remove('$..*', 'available');
        $jsonObject->remove('$', 'Bike models');
        $this->assertEquals(
            json_decode(
                '{ "store": {
    "book": [
      { "category": "reference",
        "author": "Nigel Rees",
        "title": "Sayings of the Century"
      },
      { "category": "fiction",
        "author": "Evelyn Waugh",
        "title": "Sword of Honour"
      },
      { "category": "fiction",
        "author": "Herman Melville",
        "title": "Moby Dick",
        "isbn": "0-553-21311-3"
      },
      { "category": "fiction",
        "author": "J. R. R. Tolkien",
        "title": "The Lord of the Rings",
        "isbn": "0-395-19395-8"
      }
    ],
    "bicycle": {
      "color": "red",
      "model": null,
      "sku-number": "BCCLE-0001-RD"
    }
  },
  "authors": [
    "Nigel Rees",
    "Evelyn Waugh",
    "Herman Melville",
    "J. R. R. Tolkien"
  ]
}'
                , true
            )
            , $jsonObject->getValue()
        );
    }

    public function testGetJson()
    {
        $jsonObject = new JsonObject();
        $jsonObject
            ->add('$', 41, 'size')
            ->add('$', 'black', 'color')
            ->add('$', array(), 'meta')
            ->add('$.meta', 0xfe34, 'code');
        $this->assertEquals('{"size":41,"color":"black","meta":{"code":65076}}', $jsonObject->getJson());
    }

    public function testGetJsonWithoutOptionsBitmask()
    {
        $jsonObject = new JsonObject();
        $jsonObject
            ->add('$', 'Ö Kent C. Dodds', 'author')
            ->add('$', 'À First Timers Only', 'title')
            ->add('$', array(), 'volunteers')
            ->add('$.volunteers[0]', 'Fayçal', 'name');
        $expectedJson = '{"author":"\u00d6 Kent C. Dodds","title":"\u00c0 First Timers Only","volunteers":[{"name":"Fay\u00e7al"}]}';
        $this->assertEquals($expectedJson, $jsonObject->getJson());
    }

    public function testGetJsonWithOptionsBitmask()
    {
        $jsonObject = new JsonObject();
        $jsonObject
            ->add('$', 'Ö Kent C. Dodds', 'author')
            ->add('$', 'À First Timers Only', 'title')
            ->add('$', array(), 'volunteers')
            ->add('$.volunteers[0]', 'Fayçal', 'name');
$expectedJson = <<<EOF
{
    "author": "Ö Kent C. Dodds",
    "title": "À First Timers Only",
    "volunteers": [
        {
            "name": "Fayçal"
        }
    ]
}
EOF;
        $this->assertEquals($expectedJson, $jsonObject->getJson(JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
    }

    public function testMagickMethods()
    {
        $jsonObject = new JsonObject($this->json);
        $this->assertEquals(array('0-553-21311-3', '0-395-19395-8'), $jsonObject->{'$..*.isbn'});
        $jsonObject->{'$.store.bicycle.color'} = 'green';
        $this->assertEquals(array('green'), $jsonObject->{'$.store.bicycle.color'});
        $jsonObject = new JsonObject();
        $jsonObject
            ->add('$', 41, 'size')
            ->add('$', 'black', 'color')
            ->add('$', array(), 'meta')
            ->add('$.meta', 0xfe34, 'code');
        $this->assertEquals('{"size":41,"color":"black","meta":{"code":65076}}', (string) $jsonObject);
    }

    /**
     * testConstructErrors
     *
     * @param string $jsonPath jsonPath
     *
     * @return void
     * @dataProvider testConstructErrorsProvider
     */
    public function testConstructErrors($json, $message)
    {
        $exception = null;
        try {
            $jsonObject = new JsonObject($json);
        } catch (InvalidJsonException $e) {
            $exception = $e;
        }
        $this->assertEquals($exception->getMessage(), $message);
    }

    public function testConstructErrorsProvider()
    {
        return array(
            array(5, 'value does not encode a JSON object.'),
            array('{"invalid": json}', 'string does not contain a valid JSON object.')
        );
    }

    /**
     * testParsingErrors
     *
     * @param string $jsonPath jsonPath
     *
     * @return void
     * @dataProvider testParsingErrorsProvider
     */
    public function testParsingErrors($jsonPath, $token)
    {
        $jsonObject = new JsonObject($this->json);
        $exception = null;
        try {
            $jsonObject->get($jsonPath);
        } catch (InvalidJsonPathException $e) {
            $exception = $e;
        }
        $this->assertEquals($exception->getMessage(), "Error in JSONPath near '" . $token . "'");
    }

    public function testParsingErrorsProvider()
    {
        return array(
            array('$[store', '[store'),
            array('$[{fail}]', '{fail}'),
            array('a.bc', 'a.bc'),
            array("$.store.book[?(@.title in ['foo']])]", "[?(@.title in ['foo']])]"),
            array("$.store.book[?(@.title in [['foo'])]", "[?(@.title in [['foo'])]"),
            array("$.store.book[?(@.title in ['foo')]", "[?(@.title in ['foo')]"),
            array("$.store.book[?(@.title in 'foo'])]", "[?(@.title in 'foo'])]"),
            array("$.store.book[?(@.title in 'foo')]", " in 'foo'"),
            array("$.store.book[?(@.title ['foo'])]", " ['foo']")
        );
    }

    public function testGetJsonObjects()
    {
        $jsonObject = new JsonObject($this->json);
        $childs = $jsonObject->getJsonObjects('$.store.book[*]');
        foreach ($childs as $key => $book) {
            $book->set('$.price', $key);
            $this->assertEquals($jsonObject->{'$.store.book[' . $key . ']'}[0], $book->getValue());
        }
        $this->assertEquals(4, count($childs));

        $jsonObject = new JsonObject($this->json, true);
        $bike = $jsonObject->getJsonObjects('$.store.bicycle');
        $bike->set('$.price', 412);
        $this->assertEquals($jsonObject->{'$.store.bicycle'}, $bike->getValue());

        $this->assertEquals(false, $jsonObject->getJsonObjects('$.abc'));
        $this->assertEquals([], $jsonObject->getJsonObjects('$[abc, 234f]'));
    }

    // Bug when using negative index triggers DivisionByZeroError
    // https://github.com/Galbar/JsonPath-PHP/issues/60
    public function testNegativeIndexOnEmptyArray() {
        $object = new \JsonPath\JsonObject('{"data": []}');
        $this->assertEquals([], $object->get('$.data[-1]'));

        $object = new \JsonPath\JsonObject('{"data": [{"id": 1},{"id": 2}]}');
        $this->assertEquals([], $object->get('$.data[-5].id'));

        $object = new \JsonPath\JsonObject('{"data": [{"id": 1}]}');
        $this->assertEquals($object->get('$.data[-1].id'), [1]);

        $object = new \JsonPath\JsonObject('{"data": [{"id": 1},{"id": 2}]}');
        $this->assertEquals($object->get('$.data[-1].id'), [2]);

        $object = new \JsonPath\JsonObject('{"data": []}');
        $this->assertEquals([], $object->get('$.data[1].id'));

        $object = new \JsonPath\JsonObject('{"data": [{"id": 1},{"id": 2}]}');
        $this->assertEquals([], $object->get('$.data[3].id'));
    }
}
