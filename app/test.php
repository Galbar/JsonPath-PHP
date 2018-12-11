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

include __DIR__ . '/../vendor/autoload.php';

use JsonPath\JsonObject;
use JsonPath\InvalidJsonException;
use JsonPath\InvalidJsonPathException;

$json = '
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
      "available": true
    }
  },
  "authors": [
    "Nigel Rees",
    "Evelyn Waugh",
    "Herman Melville",
    "J. R. R. Tolkien"
  ]
}
';

switch (count($argv)) {
    case 1:
        print "Usage: $argv[0] <jsonpath> [<file to json>]\n";
        print "If no json file is given it defaults to the json specified in http://goessner.net/articles/JsonPath/index.html#e3\n";
        die();
        break;
    case 3:
        $json = file_get_contents($argv[2]);
    default:
        $jsonPath = $argv[1];
}

try {
    $jsonObject = new JsonObject($json);
} catch (InvalidJsonException $e) {
    print "Invalid JSON error: '" . $e->getMessage() . "'\r\n";
    die();
}

try {
    $r = $jsonObject->get($jsonPath);
} catch (InvalidJsonPathException $e) {
    print "Invalid JSONPath error: '" . $e->getMessage() . "'\r\n";
    die();
}

if ($r === false) {
    print "false";
} else {
    print json_encode($r);
}
print "\r\n";
