<?php
namespace Tests;

use JsonPath\JsonObject;
use JsonPath\InvalidJsonException;
use JsonPath\InvalidJsonPathException;

/**
 * Class JsonObjectTest
 * @author Kyrylo Kostiukov
 */
class JsonObjectToTableTest extends \PHPUnit_Framework_TestCase
{
    private $json = '
{ "store": {
    "name":"My Store",
    "book": [
      { "category": "reference",
        "author": "Nigel Rees",
        "title": "Sayings of the Century",
        "price": 8.95,
        "available": true,
        "authors": ["Nigel Rees"]
      },
      { "category": "fiction",
        "author": "Evelyn Waugh",
        "title": "Sword of Honour",
        "price": 12.99,
        "available": false,
        "authors": []
      },
      { "category": "fiction",
        "author": "Herman Melville",
        "title": "Moby Dick",
        "isbn": "0-553-21311-3",
        "price": 8.99,
        "available": true,
        "authors": ["Nigel Rees"]
      },
      { "category": "fiction",
        "author": "J. R. R. Tolkien",
        "title": "The Lord of the Rings",
        "isbn": "0-395-19395-8",
        "price": 22.99,
        "available": false,
        "authors": ["Evelyn Waugh", "J. R. R. Tolkien"]
      }
    ],
    "bicycle": {
      "color": "red",
      "price": 19.95,
      "available": true,
      "model": null,
      "sku-number": "BCCLE-0001-RD"
    },
    "bicycleSet": [{
      "color": "red",
      "price": 19.95,
      "available": true,
      "model": null,
      "sku-number": "BCCLE-0001-RD"
    },{
      "color": "green",
      "price": 19.75,
      "available": false,
      "model": null,
      "sku-number": "RCADF-0002-CQ"
    }]
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
     * @param string $jsonPath jsonPath
     * @param bool $testReference
     *
     * @return void
     *
     * @throws InvalidJsonException
     * @throws InvalidJsonPathException
     * @dataProvider providerForTestGet
     */
    public function testGet($expected, $jsonPath)
    {
        $jsonObject = new JsonObject($this->json);
        $result = $jsonObject->getTable($jsonPath);
        $this->assertEquals($expected, $result);
    }

    public function providerForTestGet()
    {
        return array(
            array(
                array(
                    array(19.95)
                ),
                "[$.store.bicycle.price]"
            ),
            array(
                array(
                    array("My Store", 19.95, "BCCLE-0001-RD"),
                ),
                "[$.store.name,$.store.bicycle.price,$.store.bicycle.sku-number]"
            ),
            array(
                array(
                    array(8.95),
                    array(12.99),
                    array(8.99),
                    array(22.99)
                ),
                "[$.store.book[*].price]"
            ),
            array(
                array(
                    array("My Store", 8.95, "Nigel Rees"),
                    array("My Store", 12.99, null),
                    array("My Store", 8.99, "Nigel Rees"),
                    array("My Store", 22.99, "Evelyn Waugh"),
                    array("My Store", 22.99, "J. R. R. Tolkien")
                ),
                "[$.store.name,$.store.book[*].price,$.store.book[*].authors[*]]"
            ),
            array(
                array(
                    array(8.95, "Nigel Rees", "My Store"),
                    array(12.99, null, "My Store"),
                    array(8.99, "Nigel Rees", "My Store"),
                    array(22.99, "Evelyn Waugh", "My Store"),
                    array(22.99, "J. R. R. Tolkien", "My Store")
                ),
                "[$.store.book[*].price,$.store.book[*].authors[*],$.store.name]"
            ),
            array(
                array(
                    array(8.95, "Nigel Rees", "Sayings of the Century", "My Store"),
                    array(12.99, null, "Sword of Honour", "My Store"),
                    array(8.99, "Nigel Rees", "Moby Dick", "My Store"),
                    array(22.99, "Evelyn Waugh", "The Lord of the Rings", "My Store"),
                    array(22.99, "J. R. R. Tolkien", "The Lord of the Rings", "My Store")
                ),
                "[$.store.book[*].price,$.store.book[*].authors[*],$.store.book[*].title,$.store.name]"
            ),
            array(
                array(
                    array(8.95, 8.95, "Nigel Rees", "Sayings of the Century", "My Store"),
                    array(12.99, 12.99, null, "Sword of Honour", "My Store"),
                    array(8.99, 8.99, "Nigel Rees", "Moby Dick", "My Store"),
                    array(22.99, 22.99, "Evelyn Waugh", "The Lord of the Rings", "My Store"),
                    array(22.99, 22.99, "J. R. R. Tolkien", "The Lord of the Rings", "My Store")
                ),
                "[$.store.book[*].price,$.store.book[*].price,$.store.book[*].authors[*],$.store.book[*].title,$.store.name]"
            )
        );
    }
}
